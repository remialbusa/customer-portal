<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\SignatureBlob;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Persists signature PNGs to local storage (Laravel's `local` disk by
 * default) and returns a URL the form / monday.com can reference.
 *
 * Why we store locally first and upload to Monday later:
 *   - Customer signature is captured on a tablet in a lab basement
 *     with no network. We can't talk to Monday at submit time.
 *   - The form's offline layer stores the base64 in IndexedDB; the
 *     server, when it eventually receives the DTO, decodes and writes
 *     the raw PNG to the `signatures/` folder.
 *   - The SyncPendingTsrReports drainer later uploads the file to
 *     Monday's file column (using the GraphQL `add_file_to_column`
 *     mutation) and patches the TSR row with the monday file id.
 *   - This way the TSR is "valid" (signature present) the moment it
 *     is submitted, and "synced" (signature on Monday) after the
 *     drainer runs.
 */
class SignatureStorage
{
    public function __construct(
        protected ?Filesystem $disk = null,
        protected string $folder = 'signatures',
    ) {
        $this->disk ??= Storage::disk('local');
    }

    /**
     * Persist the signature, return the relative path on the disk.
     */
    public function store(SignatureBlob $blob, string $localId, string $role): string
    {
        if (! $blob->isValid()) {
            throw new InvalidArgumentException(
                "Signature for role '{$role}' failed validation (bad mime or empty pad)."
            );
        }

        $bytes = $blob->rawBytes();
        if ($bytes === false) {
            throw new RuntimeException("Could not base64-decode signature for role '{$role}'.");
        }

        $ext = $blob->mimeType() === 'image/jpeg' ? 'jpg' : 'png';
        $path = "{$this->folder}/{$localId}-{$role}.{$ext}";

        $this->disk->put($path, $bytes);

        return $path;
    }

    /**
     * Public URL the TSR row should embed (relative to /storage if the
     * `local` disk is symlinked, absolute otherwise). The form's
     * preview pane uses this.
     */
    public function url(string $path): string
    {
        // For the `local` disk we return a storage:// path that the
        // controller can rewrite to a temporary signed URL on demand.
        // Keeping the abstraction here so a future S3 swap is a one-line
        // change in the disk binding.
        return $this->disk->url($path);
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }
}
