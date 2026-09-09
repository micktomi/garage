@extends('layouts.workshop')

@section('title', 'Πίνακας Ελέγχου — Συνεργείο')
@section('header-title', 'Πίνακας Ελέγχου')
@section('header-subtitle', $todayLabel)
@section('body-class', 'ws-dashboard-page')

@section('content')
    <div class="ws-dashboard">
        <header class="ws-dashboard-header">
            <div>
                <p class="ws-dashboard-kicker">Garage Manager</p>
                <h1 class="ws-dashboard-title">Ροή συνεργείου</h1>
                <p class="ws-dashboard-subtitle"><span class="ws-dashboard-subtitle-prefix">Η σημερινή εικόνα του συνεργείου · </span><span class="ws-dashboard-date">{{ $todayLabel }}</span></p>
            </div>

            <span class="ws-system-status" role="status" aria-label="Το σύστημα λειτουργεί κανονικά">
                <span class="ws-system-status-dot" aria-hidden="true"></span>
                Σύστημα ενεργό
            </span>
        </header>

        <div class="ws-dashboard-controls">
            <form
                action="{{ route('workshop.search') }}"
                method="GET"
                class="ws-search-form"
                role="search"
                x-data="{ query: @js((string) request('q')) }"
            >
                <label for="workshop-search" class="ws-sr-only">Αναζήτηση συνεργείου</label>
                <button type="submit" class="ws-search-submit" aria-label="Αναζήτηση">
                    <svg class="ws-search-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607 0A7.5 7.5 0 0 1 5.196 15.803Z" />
                    </svg>
                </button>
                <input
                    id="workshop-search"
                    x-ref="searchInput"
                    class="ws-search-input"
                    type="search"
                    name="q"
                    placeholder="Πινακίδα, πελάτης, τηλέφωνο…"
                    autocomplete="off"
                    autocorrect="off"
                    spellcheck="false"
                    value="{{ request('q') }}"
                    x-model="query"
                >
                <button
                    type="button"
                    class="ws-search-clear"
                    x-cloak
                    x-show="query.length > 0"
                    x-on:click="query = ''; $nextTick(() => $refs.searchInput.focus())"
                    aria-label="Καθαρισμός αναζήτησης"
                    title="Καθαρισμός αναζήτησης"
                >
                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </form>

            <a href="{{ route('workshop.work-orders.create') }}" class="ws-primary-action">
                <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Νέα εντολή εργασίας
            </a>
        </div>

        {{-- Ό,τι βρίσκεται φυσικά στο συνεργείο τώρα. Δεν μοντελοποιούνται
             θέσεις ή χωρητικότητα, οπότε δεν εμφανίζονται κενές θέσεις — το
             πλέγμα δείχνει έως 8 οχήματα και τα υπόλοιπα ζουν πίσω από τον
             σύνδεσμο «Όλες οι εντολές». --}}
        <section class="ws-bays" aria-label="Οχήματα στο συνεργείο">
            <div class="ws-bays-head">
                <div>
                    <p class="ws-bays-kicker">Ζωντανή ροή</p>
                    <h2 class="ws-bays-title">Στο συνεργείο <span>· {{ $vehiclesInShop }} {{ $vehiclesInShop === 1 ? 'όχημα' : 'οχήματα' }}</span></h2>
                </div>
                <a href="{{ route('workshop.work-orders.index') }}" class="ws-bays-link">
                    Όλες οι εντολές
                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                </a>
            </div>

            <div class="ws-bay-grid">
                @forelse($inShopWorkOrders as $order)
                    <x-workshop.bay-card :order="$order" />
                @empty
                    <x-workshop.empty-state icon="clipboard" title="Κανένα όχημα στο συνεργείο">
                        <a href="{{ route('workshop.work-orders.create') }}">Άνοιγμα νέας εντολής</a>
                    </x-workshop.empty-state>
                @endforelse
            </div>
        </section>

        <div class="ws-dashboard-body">
            <section class="ws-dashboard-primary" aria-label="Πρόσφατες ανοιχτές εντολές">
                <div class="ws-panel ws-orders-panel">
                    <x-workshop.section-header
                        title="Ανοιχτές εντολές"
                        :href="route('workshop.work-orders.index')"
                        link="Όλες οι εντολές"
                        mobile-link="Όλες"
                    />

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
                <section class="ws-panel ws-day-board" aria-label="Σύνοψη ημέρας και ΚΤΕΟ">
                    <div class="ws-day-board-head">
                        <div>
                            <p class="ws-day-board-kicker">Ημέρα εργασίας</p>
                            <h2>Σήμερα</h2>
                        </div>
                        <time datetime="{{ now()->toDateString() }}">{{ $todayLabel }}</time>
                    </div>

                    <div class="ws-ops-list" aria-label="Σύνοψη ημέρας">
                        <div class="ws-ops-item ws-ops-item--appointments">
                            <span class="ws-ops-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5m-15 12h13.5a1.5 1.5 0 0 0 1.5-1.5v-13.5a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v13.5a1.5 1.5 0 0 0 1.5 1.5Zm6.75-8.625v3.75h3" />
                                </svg>
                            </span>
                            <span class="ws-ops-label">Ραντεβού σήμερα</span>
                            <strong>{{ $todayAppointments }}</strong>
                        </div>

                        <div class="ws-ops-item ws-ops-item--danger">
                            <span class="ws-ops-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.053 3.38c.866-1.5 3.032-1.5 3.898 0l7.352 12.747ZM12 16.5h.008v.008H12V16.5Z" />
                                </svg>
                            </span>
                            <span class="ws-ops-label">ΚΤΕΟ έληξαν</span>
                            <strong>{{ $expiredKteo }}</strong>
                        </div>

                        <div class="ws-ops-item ws-ops-item--parts">
                            <span class="ws-ops-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25M21 7.5v9l-9 5.25m0-9L3 7.5m9 5.25v9M3 7.5v9l9 5.25" />
                                </svg>
                            </span>
                            <span class="ws-ops-label">Αναμονή ανταλλακτικών</span>
                            <strong>{{ $awaitingParts }}</strong>
                        </div>
                    </div>

                    <div class="ws-day-board-section">
                        <div class="ws-day-board-section-head">
                            <h3>Έλεγχοι ΚΤΕΟ</h3>
                            <a href="{{ route('workshop.kteo') }}">Προβολή όλων</a>
                        </div>

                        @if($expiredKteoVehicles->isEmpty() && $expiringKteoVehicles->isEmpty())
                            <div class="ws-list">
                                <x-workshop.empty-state icon="calendar" title="Δεν υπάρχουν λήξεις ΚΤΕΟ εντός 30 ημερών">
                                    <a href="{{ route('workshop.vehicles.create') }}">Καταχώρηση οχήματος</a>
                                </x-workshop.empty-state>
                            </div>
                        @endif

                        @if($expiredKteoVehicles->isNotEmpty())
                            <h4 class="ws-kteo-subhead ws-kteo-subhead--expired">Έληξαν <span>· {{ $expiredKteo }}</span></h4>

                            <div class="ws-list">
                                @foreach($expiredKteoVehicles as $vehicle)
                                    <x-workshop.kteo-row :vehicle="$vehicle" />
                                @endforeach
                            </div>
                        @endif

                        @if($expiringKteoVehicles->isNotEmpty())
                            <h4 class="ws-kteo-subhead">Λήγουν σύντομα <span>· {{ $expiringKteo }}</span></h4>

                            <div class="ws-list">
                                @foreach($expiringKteoVehicles as $vehicle)
                                    <x-workshop.kteo-row :vehicle="$vehicle" />
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="ws-day-tools" aria-labelledby="quick-actions-title">
                        <h3 id="quick-actions-title">Γρήγορη καταχώρηση</h3>
                        <div class="ws-quick-actions">
                            <a href="{{ route('workshop.customers.create') }}" class="ws-quick-action">
                                <span class="ws-quick-action-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z"/>
                                    </svg>
                                </span>
                                <span>Πελάτης</span>
                                <svg class="ws-quick-action-plus" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </a>

                            <a href="{{ route('workshop.vehicles.create') }}" class="ws-quick-action">
                                <span class="ws-quick-action-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 17.25.75-6 1.5-3h9l1.5 3 .75 6M3.75 14.25h16.5M6.75 17.25v1.5m10.5-1.5v1.5"/>
                                    </svg>
                                </span>
                                <span>Όχημα</span>
                                <svg class="ws-quick-action-plus" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </a>

                            <a href="{{ route('workshop.work-orders.create') }}" class="ws-quick-action">
                                <span class="ws-quick-action-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6m-6 4.5h6m-6 4.5h3m-6.75 6h13.5A1.5 1.5 0 0 0 20.25 18.75v-15a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v15a1.5 1.5 0 0 0 1.5 1.5Z"/>
                                    </svg>
                                </span>
                                <span>Εντολή</span>
                                <svg class="ws-quick-action-plus" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
