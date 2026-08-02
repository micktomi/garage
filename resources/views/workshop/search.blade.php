@extends('layouts.workshop')

@section('title', 'Οχήματα — Συνεργείο')
@section('header-title', 'Οχήματα')

@push('styles')
<style>
    .sr-search-form {
        display: flex;
        flex-wrap: wrap;
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
</style>
@endpush

@section('content')

<header class="ws-page-hero">
    <div class="ws-page-hero-copy">
        <h1 class="ws-page-display-title">Οχήματα</h1>
        <p class="ws-page-subtitle">Οχήματα, στοιχεία πελατών και ενεργές εντολές.</p>
    </div>
    <div class="ws-page-actions">
        <a href="{{ route('workshop.vehicles.create') }}" class="ws-primary-action">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Νέο όχημα
        </a>
    </div>
</header>

<form method="GET" action="{{ route('workshop.search') }}" class="sr-search-form">
    <input type="text" name="q" value="{{ $q }}" class="sr-search-input"
        placeholder="Πινακίδα, όνομα πελάτη ή τηλέφωνο…" autocomplete="off" autofocus>
    <button type="submit" class="ws-secondary-action">Αναζήτηση</button>
</form>

@if($vehicles->isEmpty())
    <div class="sr-empty">
        <div class="sr-empty-title">{{ $q === '' ? 'Δεν υπάρχουν καταχωρημένα οχήματα' : 'Δεν βρέθηκαν οχήματα' }}</div>
        <div class="sr-empty-sub">
            {{ $q === '' ? 'Προσθέστε το πρώτο όχημα για να ξεκινήσετε.' : 'Δοκιμάστε διαφορετική πινακίδα, όνομα ή τηλέφωνο.' }}
        </div>
    </div>
@else
    <div class="ws-card-grid ws-card-grid--two-up sr-list">
        @foreach($vehicles as $vehicle)
            @php
                $openOrder = $vehicle->workOrders->first();
                $openCount = $vehicle->workOrders->count();
            @endphp
            <article class="ws-panel ws-operational-card sr-card">
                <a
                    href="{{ route('workshop.vehicles.edit', $vehicle) }}"
                    class="ws-operational-card__overlay sr-card-overlay"
                    aria-label="Επεξεργασία οχήματος {{ $vehicle->plate_number ?? '—' }}"
                ></a>

                <header class="ws-operational-card__head">
                    <x-workshop.plate :value="$vehicle->plate_number ?? '—'" />
                    @if($openCount > 0)
                        <span class="ws-operational-card__count">{{ $openCount }} {{ $openCount === 1 ? 'ανοιχτή εντολή' : 'ανοιχτές εντολές' }}</span>
                    @endif
                </header>

                <div class="ws-operational-card__body">
                    <div class="ws-operational-card__identity">
                        <h2 class="ws-operational-card__title" title="{{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) ?: '—' }}{{ $vehicle->year ? ' · '.$vehicle->year : '' }}">
                        {{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) ?: '—' }}
                        @if($vehicle->year) · {{ $vehicle->year }} @endif
                        </h2>
                        <span class="ws-operational-card__subtitle" title="{{ $vehicle->customer?->full_name ?? '—' }}">{{ $vehicle->customer?->full_name ?? '—' }}</span>
                    </div>
                    @if($vehicle->customer?->phone)
                        <a
                            href="tel:{{ $vehicle->customer->phone }}"
                            class="ws-operational-card__interactive ws-operational-card__inline-action ws-operational-card__subtitle sr-card-action"
                            aria-label="Κλήση {{ $vehicle->customer->full_name }}"
                        >{{ $vehicle->customer->phone }}</a>
                    @endif
                    <div class="ws-operational-card__meta">
                        @if($vehicle->mileage !== null)
                            <span>{{ number_format($vehicle->mileage, 0, ',', '.') }} km</span>
                        @endif
                        <span>ΚΤΕΟ {{ $vehicle->kteo_expires_at ? $vehicle->kteo_expires_at->translatedFormat('d M Y') : '—' }}</span>
                    </div>
                </div>

                <footer class="ws-operational-card__footer">
                    <a href="{{ route('workshop.vehicles.edit', $vehicle) }}" class="ws-card-action ws-operational-card__interactive sr-card-action">
                        Επεξεργασία
                    </a>
                    @if($openOrder)
                        <a href="{{ route('workshop.work-orders.show', $openOrder) }}" class="ws-card-action ws-operational-card__interactive sr-card-action">
                            Προβολή εντολής
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                            </svg>
                        </a>
                    @endif
                    <a
                        href="{{ route('workshop.work-orders.create', ['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id]) }}"
                        class="ws-card-action ws-card-action--primary ws-operational-card__interactive sr-card-action"
                    >
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Νέα εντολή εργασίας
                    </a>
                </footer>
            </article>
        @endforeach
    </div>

    @if($vehicles instanceof \Illuminate\Contracts\Pagination\Paginator && $vehicles->hasPages())
        <div class="ws-pagination sr-pagination">
            {{ $vehicles->onEachSide(1)->links() }}
        </div>
    @endif
@endif

@endsection
