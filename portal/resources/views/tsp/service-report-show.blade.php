<x-app-layout>
    <x-slot:title>Service Report #{{ $report->id }}</x-slot:title>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Service Report #{{ $report->id }}</h1>
            <a href="{{ route('tsp.tickets.show', ['id' => $report->monday_ticket_id]) }}" class="btn btn-link">
                ← Back to ticket
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">TSP</dt>
                    <dd class="col-sm-9">{{ $user?->name ?? '—' }}</dd>

                    <dt class="col-sm-3">Ticket</dt>
                    <dd class="col-sm-9">{{ $report->monday_ticket_id }}</dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">{{ $report->service_status?->value ?? '—' }}</dd>

                    <dt class="col-sm-3">Sync state</dt>
                    <dd class="col-sm-9">{{ $report->sync_state?->value ?? '—' }}</dd>

                    <dt class="col-sm-3">Local ID</dt>
                    <dd class="col-sm-9"><code>{{ $report->local_id ?? '—' }}</code></dd>

                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9">{{ $report->created_at?->toDateTimeString() }}</dd>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
