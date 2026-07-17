<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Read-only access to the "field-deployed TSP" roster for the customer
 * ticket form's TSP picker.
 *
 * Source of truth: the `users` table, filtered to:
 *   - role in (fse, its) — the on-site technician / IT specialist roles
 *     the customer service team expects to see
 *   - email matches *@mcbtsi.com and is not an OAuth probe (so we
 *     don't surface the OAuth-test accounts the smoke tests create)
 *   - monday_id is not null — required because the People column on
 *     Monday.com needs the person id to assign the TSP. Users without
 *     a monday_id are still listed in the picker (so the customer
 *     sees the full team) but their checkbox is disabled.
 *
 * The roster is maintained by the existing PersonnelXlsxSeeder, which
 * reads the project-root "Personnel list_.xlsx" and upserts users from
 * it. Run it after HR updates the spreadsheet:
 *
 *     php artisan db:seed --class=PersonnelXlsxSeeder
 *
 * Grouped by region (NCR, NORTH LUZON, VISAYAS, MINDANAO) in the order
 * the Monday Customer User Account board uses, so the dropdown layout
 * matches what the customer service team sees.
 */
class PersonnelDirectory
{
    /** Region display order — mirrors the Monday Customer User Account board. */
    public const REGION_ORDER = [
        'NCR',
        'NORTH LUZON',
        'VISAYAS',
        'MINDANAO',
    ];

    public const REGION_LABELS = [
        'NCR'         => 'NCR (Metro Manila)',
        'NORTH LUZON' => 'North Luzon',
        'VISAYAS'     => 'Visayas',
        'MINDANAO'    => 'Mindanao',
    ];

    /**
     * @return Collection<int, array{region:string, label:string, members:Collection<int, array>>
     *         One entry per region (in REGION_ORDER), each with the FSE/ITS
     *         members sorted by name. Regions with no members are still
     *         present (with an empty members collection) so the picker
     *         header always shows.
     */
    public static function forCustomerAssignment(): Collection
    {
        $rows = User::query()
            ->whereIn('role', ['fse', 'its'])
            ->where('email', 'like', '%@mcbtsi.com')
            ->where('email', 'not like', 'oauth-%')
            ->where('email', 'not like', 'oauth-test%')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'team', 'region', 'monday_id']);

        // Group by region, then ensure every region appears in the
        // REGION_ORDER sequence (even if empty) so the picker headers
        // are always present.
        $byRegion = $rows->groupBy(fn (User $u) => $u->region ?: 'UNASSIGNED');

        return collect(self::REGION_ORDER)
            ->map(function (string $region) use ($byRegion) {
                $members = ($byRegion[$region] ?? collect())
                    ->map(fn (User $u) => [
                        'id'         => $u->id,
                        'name'       => $u->name,
                        'email'      => $u->email,
                        'role'       => $u->role,
                        'team'       => $u->team,
                        'region'     => $u->region,
                        'monday_id'  => $u->monday_id !== null ? (int) $u->monday_id : null,
                        'assignable' => $u->monday_id !== null,
                    ])
                    ->values();

                return [
                    'region'  => $region,
                    'label'   => self::REGION_LABELS[$region] ?? $region,
                    'members' => $members,
                ];
            });
    }

    /**
     * Resolve a list of local user ids to the (deduped) Monday person ids
     * required to populate the People column on the tickets board.
     *
     * Returns the ids in the order they were requested. Throws if any of
     * the requested user ids is missing a monday_id, so the caller can
     * fail loudly rather than silently drop a TSP from the assignment.
     *
     * @param  array<int>  $userIds
     * @return array<int>
     */
    public static function resolveMondayPersonIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return [];
        }

        $users = User::whereIn('id', $userIds)->get(['id', 'monday_id']);
        $byLocalId = [];
        foreach ($users as $u) {
            $byLocalId[(int) $u->id] = $u->monday_id !== null ? (int) $u->monday_id : null;
        }

        $missing = [];
        $out = [];
        foreach ($userIds as $id) {
            if (! array_key_exists($id, $byLocalId)) {
                $missing[] = $id;
                continue;
            }
            $mid = $byLocalId[$id];
            if ($mid === null) {
                $missing[] = $id;
                continue;
            }
            $out[] = $mid;
        }
        if (! empty($missing)) {
            throw new \InvalidArgumentException(
                'One or more selected TSPs have no Monday.com person id: ' . implode(', ', $missing)
            );
        }
        return $out;
    }
}
