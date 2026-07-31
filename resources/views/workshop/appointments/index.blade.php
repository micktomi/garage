@extends('layouts.workshop')

@section('title', 'Ραντεβού — Συνεργείο')
@section('header-title', 'Ραντεβού')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────────────────── */
    .ap-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.25rem 0 1.25rem;
        flex-wrap: wrap;
    }
    .ap-page-title {
        margin: 0;
        color: var(--ws-text);
        font-size: 28px;
        font-weight: 500;
        line-height: 32px;
    }
    .ap-new-btn {
        min-height: 44px;
        padding: 8px 16px;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid transparent;
        border-radius: 8px;
        background: var(--ws-primary);
        color: var(--ws-primary-fg);
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        transition: opacity 120ms ease;
    }
    .ap-new-btn:hover { opacity: .92; }

    .ap-success {
        margin-bottom: 16px;
        padding: 12px 16px;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: var(--ws-status-ready-bg);
        color: var(--ws-status-ready-fg);
        font-size: 13px;
        font-weight: 500;
    }

    /* ── Appointment card ────────────────────────────────────── */
    .ap-list {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: stretch;
        gap: 12px;
    }
    .ap-card {
        min-width: 0;
        min-height: 116px;
        height: 100%;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        border: 1px solid var(--ws-border);
        border-radius: 12px;
        background: var(--ws-card);
        box-shadow: var(--shadow-sm);
        transition: border-color 120ms ease, background-color 120ms ease;
    }
    .ap-card:hover {
        border-color: var(--ws-border-hover);
        background: var(--ws-sunken);
    }
    .ap-card--today {
        border-color: var(--ws-border-hover);
    }
    .ap-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .ap-datetime {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .ap-date {
        overflow: hidden;
        color: var(--ws-text);
        font-size: 15px;
        font-weight: 500;
        line-height: 20px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ap-time {
        color: var(--ws-text-muted);
        font-size: 13px;
        line-height: 18px;
        font-variant-numeric: tabular-nums;
    }
    .ap-status {
        max-width: 45%;
        padding: 3px 9px;
        display: inline-flex;
        align-items: center;
        overflow: hidden;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        line-height: 18px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ap-status--scheduled {
        background: var(--ws-status-progress-bg);
        color: var(--ws-status-progress-fg);
    }
    .ap-status--in-progress {
        background: var(--ws-status-awaiting-bg);
        color: var(--ws-status-awaiting-fg);
    }
    .ap-status--completed {
        background: var(--ws-status-ready-bg);
        color: var(--ws-status-ready-fg);
    }
    .ap-status--cancelled {
        background: var(--ws-status-expired-bg);
        color: var(--ws-status-expired-fg);
    }
    .ap-status--default {
        border: 1px solid var(--ws-border);
        background: transparent;
        color: var(--ws-text-muted);
    }
    .ap-card-main {
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .ap-card-customer {
        overflow: hidden;
        color: var(--ws-text);
        font-size: 15px;
        font-weight: 500;
        line-height: 20px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ap-card-vehicle {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--ws-text-muted);
        font-size: 13px;
        line-height: 18px;
    }
    .ap-card-vehicle-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ap-card-phone {
        align-self: flex-start;
        color: var(--ws-text-muted);
        font-size: 13px;
        line-height: 18px;
    }
    .ap-card-phone:hover {
        color: var(--ws-text);
        text-decoration: underline;
    }
    .ap-card-desc {
        margin-top: 4px;
        overflow: hidden;
        color: var(--ws-text-muted);
        font-size: 12px;
        line-height: 16px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Empty state */
    .ap-empty {
        padding: 24px;
        text-align: center;
        border: 1px solid var(--ws-border);
        border-radius: 12px;
        background: var(--ws-card);
        color: var(--ws-text-muted);
    }
    .ap-empty-title {
        margin-bottom: 2px;
        color: var(--ws-text);
        font-size: 14px;
        font-weight: 500;
    }
    .ap-empty-sub {
        color: var(--ws-text-muted);
        font-size: 13px;
    }

    @media (min-width: 1120px) {
        .ap-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
@endpush

@section('content')

<div class="ap-page-header">
    <h1 class="ap-page-title">Ραντεβού</h1>
    <a href="{{ route('workshop.appointments.create') }}" class="ap-new-btn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Νέο ραντεβού
    </a>
</div>

@if(session('success'))
    <div class="ap-success">
        {{ session('success') }}
    </div>
@endif

@if($appointments->isEmpty())
    <div class="ap-empty">
        <div class="ap-empty-title">Δεν υπάρχουν προγραμματισμένα ραντεβού</div>
        <div class="ap-empty-sub">Προσθέστε το πρώτο ραντεβού για να ξεκινήσετε.</div>
    </div>
@else
    <div class="ap-list">
        @foreach($appointments as $appointment)
            @php
                $isToday = $appointment->appointment_date->isToday();
                $statusLabel = match ($appointment->status) {
                    'scheduled' => 'Προγραμματισμένο',
                    'in_progress' => 'Σε εξέλιξη',
                    'completed' => 'Ολοκληρώθηκε',
                    'cancelled' => 'Ακυρώθηκε',
                    default => $appointment->status,
                };
                $statusClass = match ($appointment->status) {
                    'scheduled' => 'scheduled',
                    'in_progress' => 'in-progress',
                    'completed' => 'completed',
                    'cancelled' => 'cancelled',
                    default => 'default',
                };
            @endphp
            <article class="ap-card {{ $isToday ? 'ap-card--today' : '' }}">
                <div class="ap-card-top">
                    <div class="ap-datetime">
                        <span class="ap-date">
                            @if($isToday)Σήμερα · @endif{{ $appointment->appointment_date->translatedFormat('d M Y') }}
                        </span>
                        <span class="ap-time">{{ $appointment->appointment_date->format('H:i') }}</span>
                    </div>
                    <span class="ap-status ap-status--{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                <div class="ap-card-main">
                    <div class="ap-card-customer">{{ $appointment->customer?->full_name ?? '—' }}</div>
                    <div class="ap-card-vehicle">
                        @if($appointment->vehicle)
                            <x-workshop.plate :value="$appointment->vehicle->plate_number ?? '—'" />
                            @if($appointment->vehicle->make || $appointment->vehicle->model)
                                <span class="ap-card-vehicle-name">
                                    {{ trim(($appointment->vehicle->make ?? '') . ' ' . ($appointment->vehicle->model ?? '')) }}
                                </span>
                            @endif
                        @else
                            <span>—</span>
                        @endif
                    </div>
                    @if($appointment->customer?->phone)
                        <a href="tel:{{ $appointment->customer->phone }}" class="ap-card-phone" aria-label="Κλήση {{ $appointment->customer->full_name }}">
                            {{ $appointment->customer->phone }}
                        </a>
                    @endif
                    @if($appointment->description)
                        <div class="ap-card-desc" title="{{ $appointment->description }}">{{ $appointment->description }}</div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif

@endsection
