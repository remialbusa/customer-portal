# TSR Signature Pad UX Revamp

## Summary
Updated the TSR signature pad UI and offline submission flow in the customer portal.

## Files changed
- `portal/resources/views/components/signature-pad.blade.php`
- `portal/resources/views/livewire/tsp/tickets/create-service-report.blade.php`

## What changed
- Improved signature pad UX with a larger, 500×200 canvas.
- Added visible "Sign here" guidance and pen icon hint.
- Added Undo and Clear controls for signatures.
- Added smoother pen strokes using quadratic Bézier interpolation.
- Added HiDPI canvas scaling for crisp rendering.
- Added online/offline submit behavior in the TSR form footer.
- Added draft autosave / offline queued indicator UI.

## Notes
- The Submit button now shows on step 3 and is connection-aware.
- Offline mode should queue TSR payloads for sync when the browser comes back online.
- Existing draft autosave functionality is preserved.

## Timestamp
- 2026-07-20
