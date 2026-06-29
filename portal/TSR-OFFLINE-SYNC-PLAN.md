# TSR offline-sync plan

## Goal

A TSP must be able to **open, fill, sign, and submit** a TSR on a
hospital tablet that has **no internet** — and have the same record
appear on the source ticket on Monday.com once connectivity is
restored, **without losing any signature, timestamp, or field value**.

The current Monday Admin agent is parked, so this plan describes the
manual hooks the portal uses, not the agent's workflow.

---

## Architecture

```
 ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
 │  Service   ──┼──► │  Portal DB  ──┼──► │  Monday.com  │
 │  Worker       │    │  (authoritative│   │  EXTERNAL-TSR│
 │  (TSP tablet) │    │   on submit)  │   │  + ticket    │
 └──────┬───────┘    └──────┬───────┘    └──────▲───────┘
        │ offline queue     │                    │
        │  (IndexedDB +     │ drainer            │
        │   service worker) │  (cron + 'online') │
        └──────────────────►┘                    │
                                                │
                                        SyncPendingTsrReports
```

* **Portal DB is authoritative at submit time.** The TSP sees a
  green "saved" flash and the form is closed. The drainer is
  best-effort.
* **The drainer is the only code that talks to Monday.** It batches
  rows whose `sync_state = pending|error` and posts them in 25-row
  chunks.
* **Customer signature is stored on the portal's `local` disk** under
  `storage/app/signatures/{local_id}-{role}.{png|jpg}`. The drainer
  uploads the file to Monday's file column once the TSR item exists.

---

## Files added (15)

| File | Purpose |
|---|---|
| `database/migrations/2026_06_21_080000_add_offline_sync_columns_to_service_reports.php` | adds `local_id`, `client_submitted_at`, `sync_state`, `sync_error`, `monday_tsr_item_id` |
| `database/migrations/2026_06_21_080500_add_signature_paths_to_service_reports.php` | adds 3 signature file paths |
| `app/Enums/SyncState.php` | 4-value enum: `pending / syncing / synced / error` |
| `app/DataTransferObjects/SignatureBlob.php` | readonly DTO: name + dataUrl + validation |
| `app/DataTransferObjects/TsrSubmissionDto.php` | main DTO matching the user's JSON shape |
| `app/Services/SignatureStorage.php` | base64 → `local` disk |
| `app/Http/Requests/StoreServiceReportRequest.php` | validation + `toDto()` |
| `app/Actions/SubmitServiceReport.php` | idempotent local persist (no monday call) |
| `app/Actions/SyncPendingTsrReports.php` | monday writer (idempotent per row) |
| `app/Http/Controllers/Tsp/ServiceReportController.php` | HTTP bridge |
| `app/Livewire/Tsp/Tickets/CreateServiceReport.php` | form component |
| `app/Livewire/Tsp/Tickets/PendingSyncBadge.php` | per-ticket status dot |
| `resources/views/livewire/tsp/tickets/create-service-report.blade.php` | form template |
| `resources/views/livewire/tsp/tickets/pending-sync-badge.blade.php` | badge template |
| `resources/views/tsp/service-report/create.blade.php` | page wrapper |
| `resources/js/portal/offline-tsr.js` | Dexie queue + `online` listener |
| `resources/js/portal/sw.js` | service worker (app shell) |
| `resources/views/partials/sw-register.blade.php` | SW registration snippet |

---

## How the offline path works

1. **Form opens** — Livewire mounts `CreateServiceReport` with
   `localId` either a new UUID or one the offline JS layer pulled
   out of IndexedDB for a half-typed draft.
2. **TSP types** — every keystroke stays in the form. We do NOT
   persist the draft to the server until the form is submitted.
3. **TSP signs** — the three `signature-pad` canvases produce
   `data:image/png;base64,…` data URLs in real time.
4. **TSP hits Save** — the form calls
   `submitTsr(payload)` in `offline-tsr.js`:
   * If `navigator.onLine`, `fetch()` the form to
     `/tsp/tickets/{id}/tsr`. On 2xx, done. On 5xx, queue.
   * If offline, queue the payload in IndexedDB.
5. **Server receives** — `ServiceReportController::store` validates
   via `StoreServiceReportRequest`, builds the DTO, calls
   `SubmitServiceReport::execute` (writes row + signatures, sets
   `sync_state = pending`).
6. **The `online` event fires** in the browser (or 60s poll, or
   cron) — `drain()` POSTs each queued payload to the same
   endpoint. Server sees the existing `local_id`, re-validates, the
   `firstOrNew` in the action returns the existing row, the
   controller responds 200, the queue drops the entry.
7. **Drainer (server side)** — `SyncPendingTsrReports::execute`
   picks up the still-`pending` rows (from any device, not just
   the current one) and pushes them to Monday:
   * create TSR item on board 5029041107
   * attach the 3 signature files
   * patch the source ticket's `status` per `TsrStatusMapper`
   * set `sync_state = synced`, `mirrored_to_monday_at = now()`

---

## Why the 3-phase split

We deliberately keep the three write paths separate:

| Path | When | Talks to Monday? | Touches DB? |
|---|---|---|---|
| `SubmitServiceReport` | every submit, online or offline | no | yes |
| Offline drainer (`/tsr/sync`) | `online` event, cron | no | reads only |
| `SyncPendingTsrReports` | cron, manual button | yes | yes (final flip) |

If the offline drainer ever confuses itself, the cron-driven
`SyncPendingTsrReports` is the safety net. If the cron is broken,
the user-driven "Sync now" button on the badge is the final safety
net. The TSR is **never** lost.

---

## Open questions (carried from the integration plan)

1. **TSR reopen behavior** — if a TSR is later edited on the TSR
   board by an FSE manager, do we re-pull and overwrite the local
   row, or is the local row the source of truth forever?
2. **Customer-signature legal binding** — is the portal-side PNG
   enough for legal, or do we need a WORM (write-once-read-many)
   storage layer? (cPanel doesn't have one; would need S3 + object
   lock.)
3. **PDF retention** — every TSR needs a generated PDF for the
   customer's records. When? Server-side cron at submit? Or
   on-demand on the ticket detail page?
4. **Customer-visible update on COMPLETED** — when the TSR moves
   to COMPLETED, what does the customer see on their dashboard?
   An auto-update post? A PDF download link? Both?

---

## Verification checklist (before first prod TSR)

- [ ] `MondayColumnIds::TSR_COL_*` match the live schema on board
      5029041107 (run `MondayColumnIds::verify()` in tinker)
- [ ] `MondayColumnIds::TICKETS_STATUS_LABEL_INDEX` matches board
      5028514175's status column indices
- [ ] `MondayColumnIds::TSR_STATUS_LABEL_INDEX` matches board
      5029041107's status column indices
- [ ] `MondayClient::createItem`, `attachFile`, `changeColumnValues`
      implemented and unit-tested
- [ ] `SyncPendingTsrReports` scheduled at `everyFiveMinutes()` in
      `routes/console.php`
- [ ] Offline form passes a 24-hour network-down soak test on a
      Pixel tablet (a member of the team has one)
- [ ] Customer signature is captured and persisted correctly
      *with* the offline path
- [ ] On reconnect, exactly 1 TSR row appears on Monday's TSR board
      per device queue entry (no duplicates)
- [ ] The response-time timer stops the first time the TSP sends
      a chat message (this lives in the chat send pipeline, not
      the TSR pipeline; tracked separately)
