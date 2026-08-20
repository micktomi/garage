@extends('layouts.workshop')

@section('title', 'Ραντεβού — Συνεργείο')
@section('header-title', 'Ραντεβού')


@section('content')

<div class="ap-page-header">
    <h1 class="ap-page-title">Ραντεβού</h1>
    <a href="{{ route('workshop.appointments.create') }}" class="ap-new-btn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Νέο ραντεβού
    </a>
</div>

@if(session('success'))
    <div class="ap-success">
        {{ session('success') }}
    </div>
@endif

@if($appointments->isEmpty())
    <div class="ap-empty">
        <div class="ap-empty-title">Δεν υπάρχουν προγραμματισμένα ραντεβού</div>
        <div class="ap-empty-sub">Προσθέστε το πρώτο ραντεβού για να ξεκινήσετε.</div>
    </div>
@else
    <div class="ap-list">
        @foreach($appointments as $appointment)
            @php
                $isToday = $appointment->appointment_date->isToday();
                $statusLabel = match ($appointment->status) {
                    'scheduled' => 'Προγραμματισμένο',
                    'in_progress' => 'Σε εξέλιξη',
                    'completed' => 'Ολοκληρώθηκε',
                    'cancelled' => 'Ακυρώθηκε',
                    default => $appointment->status,
                };
                $statusClass = match ($appointment->status) {
                    'scheduled' => 'scheduled',
                    'in_progress' => 'in-progress',
                    'completed' => 'completed',
                    'cancelled' => 'cancelled',
                    default => 'default',
                };
            @endphp
            <article class="ap-card {{ $isToday ? 'ap-card--today' : '' }}">
                <div class="ap-card-top">
                    <div class="ap-datetime">
                        <span class="ap-date">
                            @if($isToday)Σήμερα · @endif{{ $appointment->appointment_date->translatedFormat('d M Y') }}
                        </span>
                        <span class="ap-time">{{ $appointment->appointment_date->format('H:i') }}</span>
                    </div>
                    <span class="ap-status ap-status--{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                <div class="ap-card-main">
                    <div class="ap-card-customer">{{ $appointment->customer?->full_name ?? '—' }}</div>
                    <div class="ap-card-vehicle">
                        @if($appointment->vehicle)
                            <x-workshop.plate :value="$appointment->vehicle->plate_number ?? '—'" />
                            @if($appointment->vehicle->make || $appointment->vehicle->model)
                                <span class="ap-card-vehicle-name">
                                    {{ trim(($appointment->vehicle->make ?? '') . ' ' . ($appointment->vehicle->model ?? '')) }}
                                </span>
                            @endif
                        @else
                            <span>—</span>
                        @endif
                    </div>
                    @if($appointment->customer?->phone)
                        <a href="tel:{{ $appointment->customer->phone }}" class="ap-card-phone" aria-label="Κλήση {{ $appointment->customer->full_name }}">
                            {{ $appointment->customer->phone }}
                        </a>
                    @endif
                    @if($appointment->description)
                        <div class="ap-card-desc" title="{{ $appointment->description }}">{{ $appointment->description }}</div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif

@endsection
