<?php

namespace App\Services;

use App\Support\Monday\MondayColumnIds;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Read-only accessor for the monday.com Customer Details board.
 *
 * The portal uses this to verify that a customer is legitimate
 * before issuing them an account. Every customer on the portal
 * must have a corresponding row in the Customer Details board —
 * that's the auth.
 *
 * Customers are organised by:
 *   - **Group** = Region (NCR, NORTH LUZON, VISAYAS, MINDANAO)
 *   - **Status column label** = Branch (e.g. "NATIONAL CAPITAL REGION")
 *   - **Text columns** = account name, email, address, brand, model
 *
 * The board layout from the screenshot is:
 *   | User name | BRANCH | Account Name | Email | Address | USER STATUS | Date | Creation log |
 */
class MondayCustomerDirectory
{
    public function __construct(protected MondayClient $monday)
    {
    }

    public static function default(): self
    {
        return new self(MondayClient::fromConfig());
    }

    /**
     * The customer details board id, sourced from config.
     */
    public function boardId(): int
    {
        $id = (int) config('services.monday.customers_board_id');
        if ($id <= 0) {
            throw new RuntimeException(
                'MONDAY_CUSTOMERS_BOARD_ID is not set in .env'
            );
        }
        return $id;
    }

    // -----------------------------------------------------------------
    // Cache helpers
    // -----------------------------------------------------------------

    /**
     * Cached snapshot of all customers on the board. 5-minute TTL —
     * long enough that consecutive page loads don't burn the rate
     * limit, short enough that newly-added customers show up the
     * same day.
     */
    public function all(int $cacheSeconds = 300): array
    {
        return Cache::remember(
            'monday.customers.all',
            $cacheSeconds,
            fn () => $this->fetchAll()
        );
    }

    public function flushCache(): void
    {
        Cache::forget('monday.customers.all');
    }

    /**
     * @return array<int, array{
     *     id:string, name:string, group:string,
     *     branch:?string, account_name:?string, email:?string,
     *     address:?string, user_status:?string, brand:?string,
     *     model:?string, region:?string
     * }>
     */
    protected function fetchAll(): array
    {
        $items = $this->monday->getBoardItems($this->boardId(), cacheSeconds: 0);

        $out = [];
        foreach ($items as $item) {
            $out[] = $this->hydrate($item);
        }
        return $out;
    }

    // -----------------------------------------------------------------
    // Lookups
    // -----------------------------------------------------------------

    /**
     * Find a customer row by exact email match (case-insensitive).
     * Returns null if not found. This is the canonical "is this
     * person a legit customer?" check.
     */
    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        foreach ($this->all() as $row) {
            if (strtolower(trim((string) ($row['email'] ?? ''))) === $email) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Return every unique region found in the customer list.
     * Used to populate the region selector on the registration
     * form (if the invite didn't pin one).
     */
    public function regions(): array
    {
        $regions = [];
        foreach ($this->all() as $row) {
            if (! empty($row['region']) && ! in_array($row['region'], $regions, true)) {
                $regions[] = $row['region'];
            }
        }
        sort($regions);
        return $regions;
    }

    /**
     * Return every unique branch label found, optionally filtered
     * by region. Used for the branch dropdown.
     *
     * @return array<int, string>
     */
    public function branches(?string $region = null): array
    {
        $branches = [];
        foreach ($this->all() as $row) {
            if ($region !== null && ($row['region'] ?? null) !== $region) {
                continue;
            }
            $b = trim((string) ($row['branch'] ?? ''));
            if ($b !== '' && ! in_array($b, $branches, true)) {
                $branches[] = $b;
            }
        }
        sort($branches);
        return $branches;
    }

    // -----------------------------------------------------------------
    // Hydration
    // -----------------------------------------------------------------

    /**
     * Normalize a raw Monday item into the shape we expose
     * throughout the app. The monday.com API sometimes returns
     * address as a `{lat, lng, address}` JSON blob from the
     * `location_*` column type, and sometimes as plain text. We
     * handle both.
     */
    protected function hydrate(array $item): array
    {
        $cols = config('services.monday.customers_columns');

        $branch  = $this->textOrNull($item, $cols['branch']      ?? null);
        $account = $this->textOrNull($item, $cols['account_name']?? null);
        $email   = $this->textOrNull($item, $cols['email']       ?? null);
        $address = $this->locationText($item, $cols['address']   ?? null);
        $status  = $this->textOrNull($item, $cols['user_status'] ?? null);
        $brand   = $this->textOrNull($item, $cols['brand']       ?? null);
        $model   = $this->textOrNull($item, $cols['model']       ?? null);

        // The board's group title IS the region (NCR, NORTH LUZON,
        // VISAYAS, MINDANAO). The BRANCH status-column label is the
        // site / city. We surface both.
        $region = $item['group'] ?? null;

        return [
            'id'           => (string) $item['id'],
            'name'         => (string) $item['name'],
            'group'        => $region,
            'region'       => $region,
            'branch'       => $branch,
            'account_name' => $account,
            'email'        => $email,
            'address'      => $address,
            'user_status'  => $status,
            'brand'        => $brand,
            'model'        => $model,
        ];
    }

    protected function textOrNull(array $item, ?string $colId): ?string
    {
        if (! $colId) {
            return null;
        }
        $cv = $item['column_values'][$colId] ?? null;
        $text = $cv['text'] ?? null;
        if ($text === null || $text === '') {
            return null;
        }
        return (string) $text;
    }

    /**
     * The `location_*` column type in monday.com stores its value
     * as a JSON blob: { "lat": ..., "lng": ..., "address": "..." }.
     * The `text` field is the human-readable form. We prefer
     * `text` and fall back to parsing the JSON.
     */
    protected function locationText(array $item, ?string $colId): ?string
    {
        if (! $colId) {
            return null;
        }
        $cv = $item['column_values'][$colId] ?? null;
        if (! $cv) {
            return null;
        }

        $text = trim((string) ($cv['text'] ?? ''));
        if ($text !== '') {
            return $text;
        }

        $raw = $cv['value'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && ! empty($decoded['address'])) {
                return (string) $decoded['address'];
            }
        }
        return null;
    }
}
