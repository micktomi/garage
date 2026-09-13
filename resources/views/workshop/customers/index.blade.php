@extends('layouts.workshop')

@section('title', 'Πελάτες — Συνεργείο')
@section('header-title', 'Πελάτες')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────────────────── */
    .cu-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.25rem 0 1.25rem;
        flex-wrap: wrap;
    }
    .cu-page-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
    }
    .cu-new-btn {
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
    .cu-new-btn:hover { background: #fbbf24; box-shadow: 0 2px 10px rgba(245,158,11,0.4); }

    /* ── Search ──────────────────────────────────────────────── */
    .cu-search-form {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .cu-search-input {
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
    .cu-search-input::placeholder { color: var(--text-faint); }
    .cu-search-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .cu-search-btn {
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-muted);
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0 1rem;
        transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .cu-search-btn:hover { color: var(--text); border-color: var(--text-faint); }
    .cu-search-clear {
        display: inline-flex;
        align-items: center;
        font-size: 0.8125rem;
        color: var(--text-faint);
        padding: 0 0.25rem;
    }
    .cu-search-clear:hover { color: var(--text-muted); }

    /* ── Customer card ───────────────────────────────────────── */
    .cu-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .cu-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .cu-card-name {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--text);
    }
    .cu-card-contact {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    .cu-card-contact a { color: var(--info); text-decoration: none; }
    .cu-card-contact a:hover { text-decoration: underline; }
    .cu-card-divider {
        height: 1px;
        background: var(--border);
    }
    .cu-card-vehicles {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .cu-vehicle-count {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-faint);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .cu-plate-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
    }
    .cu-plate {
        display: inline-block;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 0.15rem 0.45rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        color: var(--text);
        font-family: monospace;
    }

    /* Empty state */
    .cu-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }
    .cu-empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        color: var(--text-faint);
    }
    .cu-empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.25rem;
    }
    .cu-empty-sub {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    /* Desktop enhancements */
    @media (min-width: 768px) {
        .cu-page-title { font-size: 2rem; }

        .cu-card {
            flex-direction: row;
            align-items: center;
            gap: 1.5rem;
            padding: 1.125rem 1.375rem;
        }
        .cu-card-main { flex: 0 0 260px; display: flex; flex-direction: column; gap: 0.3rem; }
        .cu-card-divider { display: none; }
        .cu-card-vehicles { flex: 1; }
    }
</style>
@endpush

@section('content')

<div class="cu-page-header">
    <h1 class="cu-page-title">Πελάτες</h1>
    <a href="{{ route('workshop.customers.create') }}" class="cu-new-btn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Νέος πελάτης
    </a>
</div>

<form method="GET" action="{{ route('workshop.customers.index') }}" class="cu-search-form">
    <input type="text" name="q" value="{{ $q }}" class="cu-search-input"
        placeholder="Όνομα, τηλέφωνο ή πινακίδα…" autocomplete="off">
    <button type="submit" class="cu-search-btn">Αναζήτηση</button>
    @if($q !== '')
        <a href="{{ route('workshop.customers.index') }}" class="cu-search-clear">Καθαρισμός</a>
    @endif
</form>

@if($customers->isEmpty())
    <div class="cu-empty">
        <svg class="cu-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
        </svg>
        @if($q !== '')
            <div class="cu-empty-title">Δεν βρέθηκαν πελάτες</div>
            <div class="cu-empty-sub">Δοκιμάστε διαφορετικό όνομα, τηλέφωνο ή πινακίδα.</div>
        @else
            <div class="cu-empty-title">Δεν υπάρχουν καταχωρημένοι πελάτες</div>
            <div class="cu-empty-sub">Προσθέστε τον πρώτο πελάτη για να ξεκινήσετε.</div>
        @endif
    </div>
@else
    <div class="cu-list">
        @foreach($customers as $customer)
            <div class="cu-card">
                <div class="cu-card-main">
                    <div class="cu-card-name">{{ $customer->full_name }}</div>
                    <div class="cu-card-contact">
                        @if($customer->phone)
                            <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                        @endif
                        @if($customer->email)
                            <span>{{ $customer->email }}</span>
                        @endif
                    </div>
                </div>

                <div class="cu-card-divider"></div>

                <div class="cu-card-vehicles">
                    <span class="cu-vehicle-count">
                        {{ $customer->vehicles_count }} {{ $customer->vehicles_count === 1 ? 'όχημα' : 'οχήματα' }}
                    </span>
                    @if($customer->vehicles->isNotEmpty())
                        <div class="cu-plate-list">
                            @foreach($customer->vehicles as $vehicle)
                                <span class="cu-plate">{{ $vehicle->plate_number ?? '—' }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
