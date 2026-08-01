@extends('layouts.workshop')

@section('title', 'Πίνακας Ελέγχου — Συνεργείο')

@section('content')
    <div class="ws-dashboard">
        <header class="ws-dashboard-header">
            <div>
                <h1 class="ws-dashboard-title">Πίνακας Ελέγχου</h1>
                <p class="ws-dashboard-subtitle">Επισκόπηση λειτουργίας συνεργείου · {{ $todayLabel }}</p>
            </div>

            <span class="ws-system-status" role="status">
                <span class="ws-system-status-dot" aria-hidden="true"></span>
                Σύστημα ενεργό
            </span>
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
            <x-workshop.stat-card label="Στο συνεργείο" :value="$openWorkOrders" icon="clipboard" accent="blue" />
            <x-workshop.stat-card label="Ραντεβού σήμερα" :value="$todayAppointments" icon="calendar" accent="violet" />
            <x-workshop.stat-card label="ΚΤΕΟ έληξαν" :value="$expiredKteo" tone="danger" icon="clock" accent="rose" />
            <x-workshop.stat-card label="Αναμονή ανταλλακτικών" :value="$awaitingParts" icon="parts" accent="amber" />
        </div>

        <div class="ws-dashboard-body">
            <section class="ws-dashboard-primary" aria-label="Πρόσφατες ανοιχτές εντολές">
                <x-workshop.section-header
                    title="Πρόσφατες Εντολές Εργασίας"
                    :href="route('workshop.work-orders.index')"
                    link="Προβολή όλων"
                />

                <div class="ws-panel">
                    @if($recentWorkOrders->isNotEmpty())
                        <div class="ws-recent-orders-head" aria-hidden="true">
                            <span>Εντολή #</span>
                            <span>Πελάτης &amp; όχημα</span>
                            <span>Κατάσταση</span>
                            <span>Άνοιγμα</span>
                            <span></span>
                        </div>
                    @endif

                    @forelse($recentWorkOrders as $order)
                        <x-workshop.work-order-row :order="$order" dashboard />
                    @empty
                        <x-workshop.empty-state icon="clipboard" title="Δεν υπάρχουν ανοιχτές εντολές">
                            <a href="{{ route('workshop.work-orders.create') }}">Άνοιγμα νέας εντολής</a>
                        </x-workshop.empty-state>
                    @endforelse
                </div>
            </section>

            <aside class="ws-dashboard-aside" aria-label="Εργαλεία ημέρας">
                <section aria-labelledby="quick-actions-title">
                    <h2 id="quick-actions-title" class="ws-dashboard-section-title">Γρήγορες Ενέργειες</h2>

                    <div class="ws-quick-actions">
                        <a href="{{ route('workshop.customers.create') }}" class="ws-quick-action">
                            <span class="ws-quick-action-main">
                                <span class="ws-quick-action-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z"/>
                                    </svg>
                                </span>
                                <span class="ws-quick-action-label">Νέος Πελάτης</span>
                            </span>
                            <svg class="ws-quick-action-plus" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </a>

                        <a href="{{ route('workshop.vehicles.create') }}" class="ws-quick-action">
                            <span class="ws-quick-action-main">
                                <span class="ws-quick-action-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 17.25.75-6 1.5-3h9l1.5 3 .75 6M3.75 14.25h16.5M6.75 17.25v1.5m10.5-1.5v1.5"/>
                                    </svg>
                                </span>
                                <span class="ws-quick-action-label">Νέο Όχημα</span>
                            </span>
                            <svg class="ws-quick-action-plus" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </a>

                        <a href="{{ route('workshop.work-orders.create') }}" class="ws-quick-action">
                            <span class="ws-quick-action-main">
                                <span class="ws-quick-action-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6m-6 4.5h6m-6 4.5h3m-6.75 6h13.5A1.5 1.5 0 0 0 20.25 18.75v-15a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v15a1.5 1.5 0 0 0 1.5 1.5Z"/>
                                    </svg>
                                </span>
                                <span class="ws-quick-action-label">Νέα Εντολή Εργασίας</span>
                            </span>
                            <svg class="ws-quick-action-plus" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </a>
                    </div>
                </section>

                <section class="ws-dashboard-kteo" aria-label="ΚΤΕΟ εντός 30 ημερών">
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
            </aside>
        </div>
    </div>
@endsection
