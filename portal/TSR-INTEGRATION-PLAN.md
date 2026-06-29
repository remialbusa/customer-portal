# TSR (Technical Service Report) Integration Plan

**Status:** Draft — pending live verification of the Tickets board (5028514175)
**Date:** 2026-06-20
**Owner:** TBD

## What this document covers

The end-to-end design for the Technical Service Report (TSR) feature:
where the data lives, how it flows between the portal, the local DB, and
monday.com, and what still needs verification before go-live.

This is the executive-data path. Every TSR submission powers the
KPI dashboard, the per-TSP performance widget, and the customer-facing
ticket status.

---

## Reference boards

| Board | Monday id | Role |
|---|---|---|
| Tickets (customer-facing) | 5028514175 | Source of truth for ticket lifecycle |
| EXTERNAL - TSR | 5029041107 | One item per TSR, mirror record |
| Customer Details | 5029331350 | (read for context, no TSR link) |

The TSR is **not** a property of the ticket. It is a separate
record that points back to the ticket via a `board_relation` column
on the TSR board. This means:

- Multiple TSRs can exist per ticket (one per TSP visit).
- The TSR is independently mutable — updating a TSR does not lock the
  ticket.
- The ticket's status is *driven by* the most recent TSR's status,
  not stored on the TSR.

---

## Data model (local)

### `service_reports` table (already migrated)

One row per submitted TSR. Mirrored from the TSR board on Monday.

| Column | Type | Source on TSR board |
|---|---|---|
| `id` | bigint | local |
| `monday_ticket_id` | string(32) | `board_relation_mm3f6835` (TSR → Tickets) |
| `monday_service_report_id` | string(32) | item id of the TSR on board 5029041107 |
| `user_id` | foreignId | TSP who wrote the report |
| `author_role` | enum(fse,its,manager,admin) | local |
| `problem_and_concerns` | text | `long_text_mks8824j` |
| `job_done` | text | `long_text_mks8y6j7` |
| `parts_replaced` | text | `text_mks8xtcq` |
| `recommendation` | text | `long_text_mksdf1jb` |
| `remarks` | text | `long_textfntkrfpg` |
| `serial_number` | string | `long_text_mkw3zweq` (kept on TSR for the audit trail) |
| `software_version` | string | `short_text7hjan9fo` |
| `machine_system` | string | `single_selectn7mh0gm` |
| `contract` | enum | `single_selectnuarkqi` |
| `customer_incharge` | string | `text_mkw29ykk` |
| `customer_incharge_email` | string | `emailpxq2qbr6` |
| `biomed_incharge` | string | `short_texton5gwzbm` |
| `biomed_email` | string | `email81d8h472` |
| `tsp_workwith_person_ids` | json | `multiple_person_mks8jn7f` |
| `login_date` | timestamp | `date_mks8wqcw` |
| `service_start_at` | timestamp | `date_mks8t42p` |
| `service_end_at` | timestamp | `date_mks8gbw0` |
| `logout_date` | timestamp | `date_mks8mvb2` |
| `call_login_time` | string(8) | `hour_mkzwcmjs` |
| `service_status` | enum | `color_mm3gbrby` |
| `total_minutes` | int | aggregated from `time_entries` at submit time |
| `mirrored_to_monday_at` | timestamp | local → TSR board sync state |
| `monday_update_id` | string | id of the `create_update` posted to the source ticket |
| `created_at` | timestamp | local |
| `updated_at` | timestamp | local |

### Relations

```
User  ──1:N──  ServiceReport  ──N:1──  (Ticket on Monday, monday_ticket_id)
```

There is **no local `tickets` table**; the ticket record lives on
Monday and is referenced by string id. The `ServiceReport` model
exposes a `belongsTo(User::class)` and the `User` model exposes
`hasMany(ServiceReport::class)`.

---

## Status flow

The executive KPI dashboard groups by `service_status` on the local
`service_reports` table, and the customer-facing ticket detail page
shows the latest TSR's status. The two are kept in sync via the
following rule:

1. TSP submits/updates a TSR on the portal.
2. Portal writes the new TSR to local `service_reports`.
3. Portal writes the new TSR row to the TSR board (5029041107),
   including the TSR's own `color_mm3gbrby` status.
4. Portal calls `TsrStatusMapper::toTicketChange()` to compute the
   column_values patch for the source ticket on 5028514175.
5. If the patch is non-null, portal writes the patch to the ticket
   via `change_multiple_column_values`.
6. If the TSR is COMPLETED, portal also queues a PDF-generation job
   (Phase 9) and posts a customer-visible update to the ticket
   (`create_update`).

### Mapping table

| TSR status | Ticket status95 label | Notes |
|---|---|---|
| OPEN        | (no change)            | ticket status is preserved |
| IN-PROGRESS | Working on it          | |
| PENDING     | Waiting for parts      | |
| ESCALATED   | Escalated              | also pages the on-call team |
| COMPLETED   | Resolved               | triggers PDF + customer update |

Implemented in `App\Support\Monday\TsrStatusMapper`.

---

## What still needs verification

Items below are placeholders in the code; each one must be confirmed
by reading the live board schema before go-live.

- [ ] **Tickets board 5028514175 — `status95` column id.** Confirm the
      exact string (currently `status95` in `MondayColumnIds`).
- [ ] **Tickets board 5028514175 — `status95` label indices.** Read the
      column's settings.labels array and fill in
      `MondayColumnIds::TICKETS_STATUS_LABEL_INDEX`. The class
      `TsrStatusMapper` will throw a clear error until this is done.
- [ ] **TSR board 5029041107 — `color_mm3gbrby` label indices.** Read
      the column's settings.labels and fill in
      `MondayColumnIds::TSR_STATUS_LABEL_INDEX`. Used when the portal
      writes a TSR's status back to the TSR board.
- [ ] **Tickets board 5028514175 — `text` column id for Subject.** Used
      when the customer-facing ticket detail page needs to show
      "Related TSRs" with a deep link. Currently `text`, must verify.
- [ ] **Mirror column handling on the Tickets board.** The Tickets
      board has mirror columns showing customer name, address, branch,
      etc. The portal does not need to write these (they are populated
      by Monday from the linked records); we only need to read them.
      Confirm the mirror column ids when wiring the ticket detail UI.

### How to run the verification

In `php artisan tinker` (or a one-off command):

```php
App\Support\Monday\MondayColumnIds::verify();
// → dumps every board + column id constant for a sanity check
```

For the live label indices:

```php
$client = App\Services\MondayClient::fromConfig();

$query = <<<'GQL'
query($boardId: ID!) {
    boards(ids: [$boardId]) {
        columns {
            id
            title
            settings_str
        }
    }
}
GQL;

$result = $client->query($query, ['boardId' => '5028514175']);
// Inspect $result['boards'][0]['columns'] for the status95 entry,
// then json_decode($col['settings_str']) to see the labels list.
```

---

## Files added in this change set

| File | Purpose |
|---|---|
| `app/Enums/ServiceStatus.php` | Backed string enum for the 5 TSR statuses |
| `app/Support/Monday/MondayColumnIds.php` | Central registry of board + column IDs |
| `app/Support/Monday/TsrStatusMapper.php` | TSR → ticket status mapping |
| `app/Models/Concerns/HasServiceStatusLabel.php` | Trait adding `statusLabel()` to models |
| `app/Models/ServiceReport.php` (edit) | Use the trait; relation to User |
| `app/Models/User.php` (edit) | `serviceReports()` hasMany |
| `TSR-INTEGRATION-PLAN.md` (this file) | Source of truth for the data flow |

## Files NOT changed

- No DB migration added. The `service_reports` migration is correct as-is.
- No monday.com writes. The mapper is read-and-decide only; the actual
  GraphQL calls are the next milestone.
- No front-end / Livewire components. The form is the next phase.

---

## Open questions for the product owner

1. **What happens to a TSR after the ticket is reopened?** Should the
   TSR's status revert to IN-PROGRESS automatically, or do we leave
   the TSR alone and let the TSP create a new one?
2. **Are TSP signatures legally binding?** If yes, we need to store
   the signature file in our own object store (S3) in addition to
   Monday; Monday's file storage is not WORM-compliant.
3. **PDF retention.** Phase 9 will generate a PDF per completed TSR.
   How long must we retain the PDF, and where? (S3? cPanel? Local?)
4. **Customer-visible update on COMPLETED.** Should the auto-posted
   update include the resolution text, or just a generic "Your service
   request has been completed" line?
