@extends('layouts.workshop')

@section('title', 'Υπενθυμίσεις ΚΤΕΟ — Συνεργείο')
@section('header-title', 'Υπενθυμίσεις ΚΤΕΟ')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────────────────── */
    .kt-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.25rem 0 1.25rem;
    }
    .kt-page-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
    }
    .kt-count-badge {
        background: var(--danger-dim);
        color: var(--danger);
        border: 1px solid rgba(248,81,73,0.25);
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
    }
    .kt-count-badge.kt-count-badge--ok {
        background: var(--success-dim);
        color: var(--success);
        border-color: rgba(63,185,80,0.25);
    }

    /* ── List / card ─────────────────────────────────────────── */
    .kt-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .kt-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .kt-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .kt-plate {
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

    .kt-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .kt-badge::before {
        content: '';
        width: 5px;
        height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .kt-badge--expired { background: var(--danger-dim); color: var(--danger); }
    .kt-badge--expired::before { background: var(--danger); }
    .kt-badge--soon { background: var(--accent-dim); color: var(--accent); }
    .kt-badge--soon::before { background: var(--accent); }

    .kt-card-main {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .kt-card-vehicle {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.3;
    }
    .kt-card-customer {
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    .kt-card-phone {
        font-size: 0.8125rem;
        color: var(--info);
        text-decoration: none;
    }
    .kt-card-phone:hover { text-decoration: underline; }

    .kt-card-divider {
        height: 1px;
        background: var(--border);
        margin: 0.125rem 0;
    }

    .kt-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .kt-card-date {
        font-size: 0.75rem;
        color: var(--text-faint);
    }
    .kt-btn-edit {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-muted);
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.35rem 0.75rem;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
        white-space: nowrap;
    }
    .kt-btn-edit:hover {
        color: var(--text);
        border-color: var(--text-faint);
        background: var(--surface-3);
    }
    .kt-btn-edit svg { width: 13px; height: 13px; stroke-width: 2.5; }

    /* Empty state */
    .kt-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }
    .kt-empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        color: var(--text-faint);
    }
    .kt-empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.25rem;
    }
    .kt-empty-sub {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    /* Desktop enhancements */
    @media (min-width: 768px) {
        .kt-page-title { font-size: 2rem; }

        .kt-card {
            flex-direction: row;
            align-items: center;
            gap: 1.25rem;
            padding: 1.125rem 1.375rem;
        }
        .kt-card-top { flex-direction: column-reverse; align-items: flex-start; flex-shrink: 0; width: 130px; gap: 0.375rem; }
        .kt-card-main { flex: 1; min-width: 0; }
        .kt-card-divider { display: none; }
        .kt-card-footer { flex-direction: column; align-items: flex-end; flex-shrink: 0; gap: 0.5rem; }
    }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="kt-page-header">
    <div>
        <div class="ws-greeting-date" style="font-size:0.75rem;color:var(--text-faint);margin-bottom:0.2rem;">
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
        <h1 class="kt-page-title">Υπενθυμίσεις ΚΤΕΟ</h1>
    </div>
    <span class="kt-count-badge {{ $vehicles->isEmpty() ? 'kt-count-badge--ok' : '' }}">{{ $vehicles->count() }}</span>
</div>

@if($vehicles->isEmpty())
    <div class="kt-empty">
        <svg class="kt-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        <div class="kt-empty-title">Δεν υπάρχουν επείγουσες λήξεις ΚΤΕΟ</div>
        <div class="kt-empty-sub">Κανένα όχημα δεν λήγει εντός 30 ημερών.</div>
    </div>
@else
    <div class="kt-list">
        @foreach($vehicles as $vehicle)
            @php
                $daysLeft = $today->diffInDays($vehicle->kteo_expires_at, false);
                $isExpired = $daysLeft < 0;
            @endphp
            <div class="kt-card">
                <div class="kt-card-top">
                    <span class="kt-plate">{{ $vehicle->plate_number ?? '—' }}</span>
                    @if($isExpired)
                        <span class="kt-badge kt-badge--expired">Ληγμένο</span>
                    @else
                        <span class="kt-badge kt-badge--soon">{{ $daysLeft }} {{ $daysLeft == 1 ? 'ημέρα' : 'ημέρες' }}</span>
                    @endif
                </div>

                <div class="kt-card-main">
                    <div class="kt-card-vehicle">
                        {{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) ?: '—' }}
                    </div>
                    <div class="kt-card-customer">{{ $vehicle->customer?->full_name ?? '—' }}</div>
                    @if($vehicle->customer?->phone)
                        <a href="tel:{{ $vehicle->customer->phone }}" class="kt-card-phone">{{ $vehicle->customer->phone }}</a>
                    @endif
                </div>

                <div class="kt-card-divider"></div>

                <div class="kt-card-footer">
                    <span class="kt-card-date">ΚΤΕΟ: {{ $vehicle->kteo_expires_at->translatedFormat('d M Y') }}</span>
                    <a href="{{ route('filament.admin.resources.vehicles.edit', $vehicle) }}" class="kt-btn-edit">
                        Επεξεργασία
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
