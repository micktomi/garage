@extends('layouts.workshop')

@section('title', 'Αρχική — Συνεργείο')

@section('content')
    <header class="ws-page-heading">
        <p class="ws-page-date">{{ $todayLabel }}</p>
        <h1 class="ws-page-title">Αρχική</h1>
    </header>

    <div class="ws-dashboard-controls">
        <form action="{{ route('workshop.search') }}" method="GET" class="ws-search-form" role="search">
            <label for="workshop-search" class="ws-sr-only">Αναζήτηση συνεργείου</label>
            <button type="submit" class="ws-search-submit" aria-label="Αναζήτηση">
                <svg class="ws-search-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607 0A7.5 7.5 0 0 1 5.196 15.803Z" />
                </svg>
            </button>
            <input
                id="workshop-search"
                class="ws-search-input"
                type="search"
                name="q"
                placeholder="Πινακίδα, πελάτης, τηλέφωνο…"
                autocomplete="off"
                autocorrect="off"
                spellcheck="false"
                value="{{ request('q') }}"
            >
        </form>

        <a href="{{ route('workshop.work-orders.create') }}" class="ws-primary-action">
            <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Νέα εντολή
        </a>
    </div>

    <div class="ws-stat-grid" aria-label="Σύνοψη ημέρας">
        <x-workshop.stat-card label="Στο συνεργείο" :value="$openWorkOrders" />
        <x-workshop.stat-card label="Ραντεβού σήμερα" :value="$todayAppointments" />
        <x-workshop.stat-card label="ΚΤΕΟ έληξαν" :value="$expiredKteo" tone="danger" />
        <x-workshop.stat-card label="Αναμονή ανταλλακτικών" :value="$awaitingParts" />
    </div>

    <div class="ws-dashboard-sections">
        <section aria-label="Ανοιχτές εντολές">
            <x-workshop.section-header
                title="Ανοιχτές εντολές"
                :href="route('workshop.work-orders.index')"
                link="Όλες οι εργασίες"
            />

            <div class="ws-list ws-work-order-grid">
                @forelse($recentWorkOrders as $order)
                    <x-workshop.work-order-row :order="$order" dashboard />
                @empty
                    <x-workshop.empty-state icon="clipboard" title="Δεν υπάρχουν ανοιχτές εντολές">
                        <a href="{{ route('workshop.work-orders.create') }}">Άνοιγμα νέας εντολής</a>
                    </x-workshop.empty-state>
                @endforelse
            </div>
        </section>

        <section aria-label="ΚΤΕΟ εντός 30 ημερών">
            <x-workshop.section-header
                title="ΚΤΕΟ εντός 30 ημερών"
                :href="route('workshop.kteo')"
                link="Όλα"
            />

            <div class="ws-list">
                @forelse($expiringVehicles as $vehicle)
                    <x-workshop.kteo-row :vehicle="$vehicle" />
                @empty
                    <x-workshop.empty-state icon="calendar" title="Δεν υπάρχουν λήξεις ΚΤΕΟ εντός 30 ημερών">
                        <a href="{{ route('workshop.vehicles.create') }}">Καταχώρηση οχήματος</a>
                    </x-workshop.empty-state>
                @endforelse
            </div>
        </section>
    </div>
@endsection
