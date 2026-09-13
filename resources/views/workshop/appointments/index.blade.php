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
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
    }
    .ap-new-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #0d1117;
        background: var(--accent);
        border: none;
        border-radius: var(--radius-sm);
        padding: 0.5rem 1rem;
        transition: background 0.15s, box-shadow 0.15s;
        box-shadow: 0 1px 6px rgba(245,158,11,0.25);
        white-space: nowrap;
    }
    .ap-new-btn:hover { background: #fbbf24; box-shadow: 0 2px 10px rgba(245,158,11,0.4); }

    /* ── Appointment card ────────────────────────────────────── */
    .ap-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .ap-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .ap-card--today {
        border-color: rgba(245,158,11,0.4);
        background: rgba(245,158,11,0.06);
    }
    .ap-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .ap-datetime {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }
    .ap-date {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--text);
    }
    .ap-time {
        font-size: 0.8125rem;
        color: var(--text-muted);
        font-variant-numeric: tabular-nums;
    }
    .ap-today-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.6875rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        background: var(--accent-dim);
        color: var(--accent);
        border: 1px solid rgba(245,158,11,0.3);
        white-space: nowrap;
    }
    .ap-card-main {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .ap-card-customer {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--text);
    }
    .ap-card-vehicle {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    .ap-plate {
        display: inline-block;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 0.1rem 0.4rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        color: var(--text);
        font-family: monospace;
    }
    .ap-card-phone {
        font-size: 0.8125rem;
        color: var(--info);
        text-decoration: none;
    }
    .ap-card-phone:hover { text-decoration: underline; }
    .ap-card-desc {
        font-size: 0.8125rem;
        color: var(--text-muted);
        line-height: 1.4;
    }

    /* Empty state */
    .ap-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }
    .ap-empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        color: var(--text-faint);
    }
    .ap-empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.25rem;
    }
    .ap-empty-sub {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    /* Desktop enhancements */
    @media (min-width: 768px) {
        .ap-page-title { font-size: 2rem; }

        .ap-card {
            flex-direction: row;
            align-items: center;
            gap: 1.25rem;
            padding: 1.125rem 1.375rem;
        }
        .ap-card-top { flex-direction: column-reverse; align-items: flex-start; flex-shrink: 0; width: 140px; gap: 0.375rem; }
        .ap-card-main { flex: 1; min-width: 0; }
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
    <div style="
        display:flex;align-items:center;gap:0.625rem;
        background:var(--success-dim);border:1px solid rgba(63,185,80,0.3);
        border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1rem;
        font-size:0.875rem;color:var(--success);font-weight:500;
    ">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

@if($appointments->isEmpty())
    <div class="ap-empty">
        <svg class="ap-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
        </svg>
        <div class="ap-empty-title">Δεν υπάρχουν προγραμματισμένα ραντεβού</div>
        <div class="ap-empty-sub">Προσθέστε το πρώτο ραντεβού για να ξεκινήσετε.</div>
    </div>
@else
    <div class="ap-list">
        @foreach($appointments as $appointment)
            @php
                $isToday = $appointment->appointment_date->isToday();
            @endphp
            <div class="ap-card {{ $isToday ? 'ap-card--today' : '' }}">
                <div class="ap-card-top">
                    <div class="ap-datetime">
                        <span class="ap-date">{{ $appointment->appointment_date->translatedFormat('d M Y') }}</span>
                        <span class="ap-time">{{ $appointment->appointment_date->format('H:i') }}</span>
                    </div>
                    @if($isToday)
                        <span class="ap-today-badge">Σήμερα</span>
                    @endif
                </div>

                <div class="ap-card-main">
                    <div class="ap-card-customer">{{ $appointment->customer?->full_name ?? '—' }}</div>
                    <div class="ap-card-vehicle">
                        @if($appointment->vehicle)
                            <span class="ap-plate">{{ $appointment->vehicle->plate_number ?? '—' }}</span>
                            @if($appointment->vehicle->make || $appointment->vehicle->model)
                                <span>{{ trim(($appointment->vehicle->make ?? '') . ' ' . ($appointment->vehicle->model ?? '')) }}</span>
                            @endif
                        @else
                            <span>—</span>
                        @endif
                    </div>
                    @if($appointment->customer?->phone)
                        <a href="tel:{{ $appointment->customer->phone }}" class="ap-card-phone">{{ $appointment->customer->phone }}</a>
                    @endif
                    @if($appointment->description)
                        <div class="ap-card-desc">{{ $appointment->description }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
