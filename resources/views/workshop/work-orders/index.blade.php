@extends('layouts.workshop')

@section('title', 'Εργασίες — Συνεργείο')

@section('content')
    <header class="ws-page-heading-row">
        <h1 class="ws-page-title">Εργασίες</h1>
        <a href="{{ route('workshop.work-orders.create') }}" class="ws-primary-action">
            <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Νέα εντολή
        </a>
    </header>

    <section>
        <x-workshop.section-header title="Ανοιχτές εντολές" />

        <div class="ws-list ws-work-order-grid">
            @forelse($workOrders as $order)
                <x-workshop.work-order-row :order="$order" variant="index" />
            @empty
                <x-workshop.empty-state icon="clipboard" title="Δεν υπάρχουν ανοιχτές εντολές">
                    <a href="{{ route('workshop.work-orders.create') }}">Άνοιγμα νέας εντολής</a>
                </x-workshop.empty-state>
            @endforelse
        </div>
    </section>
@endsection
