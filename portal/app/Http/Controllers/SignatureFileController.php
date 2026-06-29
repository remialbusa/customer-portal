<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public, signed-only signature file endpoint.
 *
 * Why this exists
 * ---------------
 * Monday.com's `add_file_to_column` mutation has been returning
 * INTERNAL_SERVER_ERROR for our TSR signatures (intermittently
 * since 2026-06-20). When that happens we still need to get the
 * signature images onto the TSR item.
 *
 * Monday's `change_column_value` mutation accepts a `link` payload
 * of shape:
 *   { "url": "https://...", "text": "TSP signature" }
 * and Monday's own web UI will fetch the URL and render a preview
 * card on the item, with a "Download" affordance. That is the
 * "Use Signature" pattern from Monday forms.
 *
 * To make that work we expose a signed URL pointing at this
 * controller, which simply streams the file off the local disk.
 * The URL is signed by Laravel (HMAC-SHA256) with a 10-minute
 * expiry, and includes the local_id + role, so it cannot be
 * guessed by third parties.
 */
class SignatureFileController extends Controller
{
    public function show(Request $request, string $localId, string $role): StreamedResponse
    {
        // Defense in depth: even though the URL is signed, double-check
        // the path stays inside storage/app/private/signatures/.
        $localId = preg_replace('/[^a-f0-9\-]/i', '', $localId);
        if ($localId === '') {
            throw new NotFoundHttpException();
        }
        $role = preg_replace('/[^a-z]/', '', $role);
        if (! in_array($role, ['tsp', 'customer', 'biomed'], true)) {
            throw new NotFoundHttpException();
        }

        $relative = "signatures/{$localId}-{$role}.png";
        $absolute = Storage::disk('local')->path($relative);
        if (! is_file($absolute)) {
            Log::warning('SignatureFileController: file not found', [
                'relative' => $relative,
                'absolute' => $absolute,
            ]);
            throw new NotFoundHttpException();
        }

        try {
            return response()->stream(function () use ($absolute) {
            $fp = fopen($absolute, 'rb');
            if ($fp) {
                fpassthru($fp);
                fclose($fp);
            }
            }, 200, [
                'Content-Type'        => 'image/png',
                'Content-Length'      => (string) filesize($absolute),
                'Cache-Control'       => 'private, max-age=60',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $e) {
            Log::error('SignatureFileController: stream failed', [
                'absolute' => $absolute,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
