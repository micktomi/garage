@extends('layouts.workshop')

@section('title', 'Αναζήτηση Πινακίδας — Συνεργείο')
@section('header-title', 'Αναζήτηση')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────────────────── */
    .sr-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.25rem 0 1.25rem;
        flex-wrap: wrap;
    }
    .sr-page-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
    }
    .sr-new-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        border-radius: var(--radius-sm);
        color: var(--ws-primary-fg);
        background: var(--ws-primary);
        font-size: 0.8125rem;
        font-weight: 700;
        white-space: nowrap;
        transition: filter 120ms ease;
    }
    .sr-new-btn:hover {
        filter: brightness(0.94);
    }

    /* ── Search form ─────────────────────────────────────────── */
    .sr-search-form {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .sr-search-input {
        flex: 1;
        background: var(--surface-3);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.65rem 0.875rem;
        font-size: 0.9375rem;
        color: var(--text);
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .sr-search-input::placeholder { color: var(--text-faint); }
    .sr-search-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .sr-search-btn {
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 600;
        color: #0d1117;
        background: var(--accent);
        border: none;
        border-radius: var(--radius-sm);
        padding: 0 1.125rem;
        transition: background 0.15s, box-shadow 0.15s;
        box-shadow: 0 1px 6px rgba(245,158,11,0.25);
    }
    .sr-search-btn:hover { background: #fbbf24; }

    /* ── Result card ─────────────────────────────────────────── */
    .sr-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .sr-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .sr-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .sr-plate {
        display: inline-block;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 0.15rem 0.5rem;
        font-size: 0.875rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        color: var(--text);
        font-family: monospace;
    }
    .sr-open-badge {
        background: var(--accent-dim);
        color: var(--accent);
        border: 1px solid rgba(245,158,11,0.25);
        font-size: 0.6875rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .sr-card-main {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .sr-card-vehicle {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--text);
    }
    .sr-card-customer {
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    .sr-card-phone {
        font-size: 0.8125rem;
        color: var(--info);
        text-decoration: none;
    }
    .sr-card-phone:hover { text-decoration: underline; }
    .sr-card-kteo {
        font-size: 0.75rem;
        color: var(--text-faint);
    }
    .sr-card-divider {
        height: 1px;
        background: var(--border);
    }
    .sr-card-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .sr-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.4rem 0.875rem;
        border-radius: var(--radius-sm);
        transition: background 0.15s, border-color 0.15s, color 0.15s;
        white-space: nowrap;
    }
    .sr-btn-show {
        color: var(--text-muted);
        background: var(--surface-2);
        border: 1px solid var(--border);
    }
    .sr-btn-show:hover { color: var(--text); border-color: var(--text-faint); background: var(--surface-3); }
    .sr-btn-new {
        color: #0d1117;
        background: var(--accent);
        border: 1px solid transparent;
    }
    .sr-btn-new:hover { background: #fbbf24; }
    .sr-btn svg { width: 13px; height: 13px; stroke-width: 2.5; }

    /* Empty state */
    .sr-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }
    .sr-empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        color: var(--text-faint);
    }
    .sr-empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.25rem;
    }
    .sr-empty-sub {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    /* Desktop enhancements */
    @media (min-width: 768px) {
        .sr-page-title { font-size: 2rem; }

        .sr-card {
            flex-direction: row;
            align-items: center;
            gap: 1.25rem;
            padding: 1.125rem 1.375rem;
        }
        .sr-card-top { flex-direction: column-reverse; align-items: flex-start; flex-shrink: 0; width: 150px; gap: 0.375rem; }
        .sr-card-main { flex: 1; min-width: 0; }
        .sr-card-divider { display: none; }
        .sr-card-footer { flex-shrink: 0; }
    }
</style>
@endpush

@section('content')

<div class="sr-page-header">
    <h1 class="sr-page-title">Αναζήτηση Πινακίδας</h1>
    <a href="{{ route('workshop.vehicles.create') }}" class="sr-new-btn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Νέο όχημα
    </a>
</div>

<form method="GET" action="{{ route('workshop.search') }}" class="sr-search-form">
    <input type="text" name="q" value="{{ $q }}" class="sr-search-input"
        placeholder="Πινακίδα, όνομα πελάτη ή τηλέφωνο…" autocomplete="off" autofocus>
    <button type="submit" class="sr-search-btn">Αναζήτηση</button>
</form>

@if($q === '')
    {{-- No query yet — show only the form, not the whole database. --}}
@elseif($vehicles->isEmpty())
    <div class="sr-empty">
        <svg class="sr-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607 0A7.5 7.5 0 0 1 5.196 15.803"/>
        </svg>
        <div class="sr-empty-title">Δεν βρέθηκαν αποτελέσματα</div>
        <div class="sr-empty-sub">Δοκιμάστε διαφορετική πινακίδα, όνομα ή τηλέφωνο.</div>
    </div>
@else
    <div class="sr-list">
        @foreach($vehicles as $vehicle)
            @php
                $openOrder = $vehicle->workOrders->first();
                $openCount = $vehicle->workOrders->count();
            @endphp
            <div class="sr-card">
                <div class="sr-card-top">
                    <span class="sr-plate">{{ $vehicle->plate_number ?? '—' }}</span>
                    @if($openCount > 0)
                        <span class="sr-open-badge">{{ $openCount }} {{ $openCount === 1 ? 'ανοιχτή εντολή' : 'ανοιχτές εντολές' }}</span>
                    @endif
                </div>

                <div class="sr-card-main">
                    <div class="sr-card-vehicle">
                        {{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) ?: '—' }}
                        @if($vehicle->year) <span style="color:var(--text-faint);">· {{ $vehicle->year }}</span> @endif
                    </div>
                    <div class="sr-card-customer">{{ $vehicle->customer?->full_name ?? '—' }}</div>
                    @if($vehicle->customer?->phone)
                        <a href="tel:{{ $vehicle->customer->phone }}" class="sr-card-phone">{{ $vehicle->customer->phone }}</a>
                    @endif
                    <div class="sr-card-kteo">
                        ΚΤΕΟ:
                        {{ $vehicle->kteo_expires_at ? $vehicle->kteo_expires_at->translatedFormat('d M Y') : '—' }}
                    </div>
                </div>

                <div class="sr-card-divider"></div>

                <div class="sr-card-footer">
                    <a href="{{ route('workshop.vehicles.edit', $vehicle) }}" class="sr-btn sr-btn-show">
                        Επεξεργασία
                    </a>
                    @if($openOrder)
                        <a href="{{ route('workshop.work-orders.show', $openOrder) }}" class="sr-btn sr-btn-show">
                            Προβολή εντολής
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                            </svg>
                        </a>
                    @endif
                    <a href="{{ route('workshop.work-orders.create', ['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id]) }}" class="sr-btn sr-btn-new">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Νέα εντολή εργασίας
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
