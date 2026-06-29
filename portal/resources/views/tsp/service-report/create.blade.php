@extends('layouts.app')

@section('title', 'New TSR — Ticket ' . $ticket->monday_item_id)

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Service Report — Ticket {{ $ticket->monday_item_id }}</h1>
            <a href="{{ route('tsp.tickets.show', ['id' => $ticket->id]) }}" class="btn btn-link">
                ← Back to ticket
            </a>
        </div>

        <livewire:tsp.tickets.create-service-report :ticket="$ticket" />
    </div>

    @include('partials.sw-register')
@endsection
