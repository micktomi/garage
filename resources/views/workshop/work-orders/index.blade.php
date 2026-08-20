@extends('layouts.workshop')

@section('title', 'Εργασίες — Συνεργείο')
@section('header-title', 'Εργασίες')


@section('content')
    <div class="wol-page">
        <header class="ws-page-hero">
            <div class="ws-page-hero-copy">
                <h1 class="ws-page-display-title">Εργασίες</h1>
                <p class="ws-page-subtitle">Οι ενεργές εντολές του συνεργείου σε μία καθαρή προβολή.</p>
            </div>

            <div class="ws-page-actions">
                <a href="{{ route('workshop.work-orders.create') }}" class="ws-primary-action">
                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Νέα εντολή
                </a>
            </div>
        </header>

        <section aria-labelledby="wol-open-orders-title">
            <div class="wol-section-heading">
                <h2 id="wol-open-orders-title" class="ws-panel-title">Ανοιχτές εντολές</h2>
                <span class="ws-panel-meta">
                    {{ $workOrders->count() }} {{ $workOrders->count() === 1 ? 'εντολή' : 'εντολές' }}
                </span>
            </div>

            <div class="ws-panel wol-grid">
                @forelse($workOrders as $order)
                    <x-workshop.work-order-row :order="$order" variant="index" />
                @empty
                    <x-workshop.empty-state icon="clipboard" title="Δεν υπάρχουν ανοιχτές εντολές">
                        <a href="{{ route('workshop.work-orders.create') }}">Άνοιγμα νέας εντολής</a>
                    </x-workshop.empty-state>
                @endforelse
            </div>
        </section>
    </div>
@endsection
