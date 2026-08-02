@extends('layouts.workshop')

@section('title', 'Πελάτες — Συνεργείο')
@section('header-title', 'Πελάτες')

@push('styles')
<style>
    .cu-search-form {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }
    .cu-search-input {
        min-width: 0;
        height: 44px;
        padding: 0 14px;
        flex: 1 1 220px;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: var(--ws-card);
        color: var(--ws-text);
        font-size: 14px;
        transition: border-color 120ms ease;
    }
    .cu-search-input::placeholder { color: var(--ws-text-muted); }
    .cu-search-input:hover { border-color: var(--ws-border-hover); }
    .cu-search-clear {
        display: inline-flex;
        align-items: center;
        min-height: 44px;
        padding: 0 8px;
        color: var(--ws-text-muted);
        font-size: 13px;
    }
    .cu-search-clear:hover { color: var(--ws-text); }

    .cu-card-email {
        flex: 1 1 180px;
        min-width: 0;
    }

    .cu-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--ws-text-muted);
    }
    .cu-empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        color: var(--ws-text-muted);
    }
    .cu-empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--ws-text);
        margin-bottom: 0.25rem;
    }
    .cu-empty-sub {
        font-size: 0.875rem;
        color: var(--ws-text-muted);
    }
</style>
@endpush

@section('content')

<header class="ws-page-hero">
    <div class="ws-page-hero-copy">
        <h1 class="ws-page-display-title">Πελάτες</h1>
        <p class="ws-page-subtitle">Στοιχεία επικοινωνίας και οχήματα πελατών.</p>
    </div>
    <div class="ws-page-actions">
        <a href="{{ route('workshop.customers.create') }}" class="ws-primary-action">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Νέος πελάτης
        </a>
    </div>
</header>

<form method="GET" action="{{ route('workshop.customers.index') }}" class="cu-search-form">
    <input type="text" name="q" value="{{ $q }}" class="cu-search-input"
        placeholder="Όνομα, τηλέφωνο ή πινακίδα…" autocomplete="off">
    <button type="submit" class="ws-secondary-action">Αναζήτηση</button>
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
    <div class="ws-card-grid ws-card-grid--two-up cu-list">
        @foreach($customers as $customer)
            <article class="ws-panel ws-operational-card cu-card">
                <a
                    href="{{ route('filament.admin.resources.customers.edit', ['record' => $customer]) }}"
                    class="ws-operational-card__overlay"
                    aria-label="Επεξεργασία πελάτη {{ $customer->full_name }}"
                ></a>

                <header class="ws-operational-card__head">
                    <div class="ws-operational-card__identity">
                        <h2 class="ws-operational-card__title" title="{{ $customer->full_name }}">{{ $customer->full_name }}</h2>
                        <div class="ws-operational-card__meta">
                        @if($customer->phone)
                                <a
                                    href="tel:{{ $customer->phone }}"
                                    class="ws-operational-card__interactive ws-operational-card__inline-action"
                                    aria-label="Κλήση {{ $customer->full_name }}"
                                >{{ $customer->phone }}</a>
                        @endif
                        @if($customer->email)
                                <span class="ws-operational-card__truncate cu-card-email" title="{{ $customer->email }}">{{ $customer->email }}</span>
                        @endif
                        </div>
                    </div>
                    <span class="ws-operational-card__count">
                        {{ $customer->vehicles_count }} {{ $customer->vehicles_count === 1 ? 'όχημα' : 'οχήματα' }}
                    </span>
                </header>

                <div class="ws-operational-card__body">
                    @if($customer->vehicles->isNotEmpty())
                        <div class="ws-operational-card__plate-list">
                            @foreach($customer->vehicles as $vehicle)
                                <x-workshop.plate :value="$vehicle->plate_number ?? '—'" />
                            @endforeach
                        </div>
                    @else
                        <span class="ws-operational-card__subtitle">Χωρίς καταχωρημένο όχημα</span>
                    @endif
                </div>

                <footer class="ws-operational-card__footer">
                    <a
                        href="{{ route('filament.admin.resources.customers.edit', ['record' => $customer]) }}"
                        class="ws-card-action ws-operational-card__interactive"
                    >Επεξεργασία</a>
                    @if($customer->vehicles->isNotEmpty())
                        <a
                            href="{{ route('workshop.work-orders.create', ['customer_id' => $customer->id]) }}"
                            class="ws-card-action ws-card-action--primary ws-operational-card__interactive"
                        >Νέα εντολή</a>
                    @endif
                </footer>
            </article>
        @endforeach
    </div>
@endif

@endsection
