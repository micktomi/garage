@extends('layouts.workshop')

@section('title', 'Οχήματα — Συνεργείο')
@section('header-title', 'Οχήματα')

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
        margin: 0;
        color: var(--ws-text);
        font-size: 28px;
        font-weight: 500;
        line-height: 32px;
    }
    .sr-new-btn {
        min-height: 44px;
        padding: 8px 16px;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid transparent;
        border-radius: 8px;
        color: var(--ws-primary-fg);
        background: var(--ws-primary);
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        transition: opacity 120ms ease;
    }
    .sr-new-btn:hover { opacity: .92; }

    /* ── Search form ─────────────────────────────────────────── */
    .sr-search-form {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
    }
    .sr-search-input {
        min-width: 0;
        height: 44px;
        flex: 1;
        padding: 0 14px;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: var(--ws-card);
        color: var(--ws-text);
        font-size: 14px;
        transition: border-color 120ms ease;
    }
    .sr-search-input::placeholder { color: var(--ws-text-muted); }
    .sr-search-input:hover { border-color: var(--ws-border-hover); }
    .sr-search-input:focus {
        border-color: var(--ws-primary);
        outline: 2px solid var(--ws-primary);
        outline-offset: 2px;
    }
    .sr-search-btn {
        min-height: 44px;
        padding: 0 16px;
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: var(--ws-card);
        color: var(--ws-text);
        font-size: 14px;
        font-weight: 500;
        transition: border-color 120ms ease, background-color 120ms ease;
    }
    .sr-search-btn:hover {
        border-color: var(--ws-border-hover);
        background: var(--ws-sunken);
    }

    /* ── Result card ─────────────────────────────────────────── */
    .sr-list {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: stretch;
        gap: 12px;
    }
    .sr-card {
        position: relative;
        min-width: 0;
        min-height: 132px;
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
    .sr-card:hover {
        border-color: var(--ws-border-hover);
        background: var(--ws-sunken);
    }
    .sr-card-overlay {
        position: absolute;
        z-index: 1;
        inset: 0;
        border-radius: inherit;
    }
    .sr-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .sr-open-badge {
        padding: 3px 9px;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: transparent;
        color: var(--ws-text-muted);
        font-size: 12px;
        font-weight: 500;
        line-height: 18px;
        white-space: nowrap;
    }
    .sr-card-main {
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .sr-card-vehicle {
        overflow: hidden;
        color: var(--ws-text);
        font-size: 15px;
        font-weight: 500;
        line-height: 20px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sr-card-customer {
        overflow: hidden;
        color: var(--ws-text-muted);
        font-size: 13px;
        line-height: 18px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sr-card-phone {
        align-self: flex-start;
        color: var(--ws-text-muted);
        font-size: 13px;
        line-height: 18px;
    }
    .sr-card-phone:hover {
        color: var(--ws-text);
        text-decoration: underline;
    }
    .sr-card-meta {
        margin-top: 4px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px 12px;
        color: var(--ws-text-muted);
        font-size: 12px;
        line-height: 16px;
    }
    .sr-card-divider {
        display: none;
    }
    .sr-card-footer {
        position: relative;
        z-index: 2;
        margin-top: auto;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }
    .sr-card-action {
        position: relative;
        z-index: 2;
    }
    .sr-btn {
        min-height: 36px;
        padding: 7px 10px;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: transparent;
        color: var(--ws-text-muted);
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        transition: color 120ms ease, border-color 120ms ease, background-color 120ms ease;
    }
    .sr-btn:hover {
        border-color: var(--ws-border-hover);
        background: var(--ws-card);
        color: var(--ws-text);
    }
    .sr-btn-new {
        color: var(--ws-primary-fg);
        background: var(--ws-primary);
        border: 1px solid transparent;
    }
    .sr-btn-new:hover {
        border-color: transparent;
        background: var(--ws-primary);
        color: var(--ws-primary-fg);
        opacity: .92;
    }
    .sr-btn svg { width: 13px; height: 13px; stroke-width: 2.5; }

    /* Empty state */
    .sr-empty {
        padding: 24px;
        text-align: center;
        border: 1px solid var(--ws-border);
        border-radius: 12px;
        background: var(--ws-card);
        color: var(--ws-text-muted);
    }
    .sr-empty-title {
        margin-bottom: 2px;
        color: var(--ws-text);
        font-size: 14px;
        font-weight: 500;
    }
    .sr-empty-sub {
        color: var(--ws-text-muted);
        font-size: 13px;
    }
    .sr-pagination {
        margin-top: 16px;
    }

    @media (min-width: 1120px) {
        .sr-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
@endpush

@section('content')

<div class="sr-page-header">
    <h1 class="sr-page-title">Οχήματα</h1>
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

@if($vehicles->isEmpty())
    <div class="sr-empty">
        <div class="sr-empty-title">{{ $q === '' ? 'Δεν υπάρχουν καταχωρημένα οχήματα' : 'Δεν βρέθηκαν οχήματα' }}</div>
        <div class="sr-empty-sub">
            {{ $q === '' ? 'Προσθέστε το πρώτο όχημα για να ξεκινήσετε.' : 'Δοκιμάστε διαφορετική πινακίδα, όνομα ή τηλέφωνο.' }}
        </div>
    </div>
@else
    <div class="sr-list">
        @foreach($vehicles as $vehicle)
            @php
                $openOrder = $vehicle->workOrders->first();
                $openCount = $vehicle->workOrders->count();
            @endphp
            <article class="sr-card">
                <a
                    href="{{ route('workshop.vehicles.edit', $vehicle) }}"
                    class="sr-card-overlay"
                    aria-label="Επεξεργασία οχήματος {{ $vehicle->plate_number ?? '—' }}"
                ></a>

                <div class="sr-card-top">
                    <x-workshop.plate :value="$vehicle->plate_number ?? '—'" />
                    @if($openCount > 0)
                        <span class="sr-open-badge">{{ $openCount }} {{ $openCount === 1 ? 'ανοιχτή εντολή' : 'ανοιχτές εντολές' }}</span>
                    @endif
                </div>

                <div class="sr-card-main">
                    <div class="sr-card-vehicle">
                        {{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) ?: '—' }}
                        @if($vehicle->year) · {{ $vehicle->year }} @endif
                    </div>
                    <div class="sr-card-customer">{{ $vehicle->customer?->full_name ?? '—' }}</div>
                    @if($vehicle->customer?->phone)
                        <a
                            href="tel:{{ $vehicle->customer->phone }}"
                            class="sr-card-phone sr-card-action"
                            aria-label="Κλήση {{ $vehicle->customer->full_name }}"
                        >{{ $vehicle->customer->phone }}</a>
                    @endif
                    <div class="sr-card-meta">
                        @if($vehicle->mileage !== null)
                            <span>{{ number_format($vehicle->mileage, 0, ',', '.') }} km</span>
                        @endif
                        <span>ΚΤΕΟ {{ $vehicle->kteo_expires_at ? $vehicle->kteo_expires_at->translatedFormat('d M Y') : '—' }}</span>
                    </div>
                </div>

                <div class="sr-card-divider"></div>

                <div class="sr-card-footer">
                    <a href="{{ route('workshop.vehicles.edit', $vehicle) }}" class="sr-btn sr-card-action">
                        Επεξεργασία
                    </a>
                    @if($openOrder)
                        <a href="{{ route('workshop.work-orders.show', $openOrder) }}" class="sr-btn sr-card-action">
                            Προβολή εντολής
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                            </svg>
                        </a>
                    @endif
                    <a
                        href="{{ route('workshop.work-orders.create', ['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id]) }}"
                        class="sr-btn sr-btn-new sr-card-action"
                    >
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Νέα εντολή εργασίας
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    @if($vehicles instanceof \Illuminate\Contracts\Pagination\Paginator && $vehicles->hasPages())
        <div class="sr-pagination">
            {{ $vehicles->onEachSide(1)->links() }}
        </div>
    @endif
@endif

@endsection
