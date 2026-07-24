@extends('layouts.workshop')

@section('title', 'Αρχική — Συνεργείο')
@section('header-title', 'Αρχική')

@push('styles')
<style>
    /* ── Page header (matches wo-/kt-/ap-page-header pattern) ─────── */
    .ws-dash-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.25rem 0 1.25rem;
    }
    .ws-dash-page-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
    }

    /* ── Search bar ─────────────────────────────────────────────── */
    .ws-search-wrap {
        position: relative;
    }
    .ws-search-icon {
        position: absolute;
        left: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: var(--text-faint);
        pointer-events: none;
    }
    .ws-search-input {
        width: 100%;
        height: 48px;
        background: rgba(255,255,255,0.055);
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 0 1rem 0 2.75rem;
        font-size: 0.9375rem;
        color: var(--text);
        outline: none;
        box-shadow: var(--highlight), var(--shadow-sm);
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        -webkit-appearance: none;
    }
    .ws-search-input::placeholder { color: var(--text-faint); }
    .ws-search-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }

    /* ── Action grid ────────────────────────────────────────────── */
    .ws-actions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.625rem;
    }

    .ws-card {
        background: var(--surface);
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
        cursor: pointer;
        box-shadow: var(--highlight), var(--shadow-sm);
        transition: background 0.15s, border-color 0.15s, transform 0.12s, box-shadow 0.15s;
        -webkit-tap-highlight-color: transparent;
        min-height: 96px;
        text-decoration: none;
        color: var(--text);
    }
    .ws-card:hover,
    .ws-card:focus-visible {
        background: var(--surface-2);
        border-color: rgba(203, 213, 225, 0.28);
        box-shadow: var(--highlight), var(--shadow-md);
        transform: translateY(-1px);
        outline: none;
    }
    .ws-card:active {
        transform: scale(0.985);
    }

    .ws-card--primary {
        background: rgba(245, 158, 11, 0.18);
        border-color: rgba(245, 158, 11, 0.42);
        box-shadow: var(--highlight), 0 12px 28px rgba(245, 158, 11, 0.10);
    }
    .ws-card--primary:hover {
        background: rgba(245, 158, 11, 0.24);
        border-color: rgba(245, 158, 11, 0.58);
    }
    .ws-card--primary .ws-card-title {
        color: #fff7ed;
    }

    .ws-card--disabled {
        opacity: 0.58;
        cursor: not-allowed;
        pointer-events: none;
        box-shadow: none;
    }

    .ws-card-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ws-card-icon svg { width: 19px; height: 19px; stroke-width: 1.8; }

    .ws-card-icon--amber { background: var(--accent-dim);  color: var(--accent); }
    .ws-card-icon--green { background: var(--success-dim); color: var(--success); }
    .ws-card-icon--gray  { background: rgba(255,255,255,0.055); color: var(--text-muted); }

    .ws-card-body {}
    .ws-card-title {
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.3;
        color: var(--text);
    }
    .ws-card-sub {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.2rem;
        line-height: 1.3;
    }

    /* ── Dashboard columns: open work orders / appointments / KTEO ── */
    .ws-dash-col-head {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 0.75rem;
    }
    .ws-dash-col-head h2 {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--text-faint);
    }
    .ws-dash-count {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.8125rem;
        color: var(--accent);
    }
    .ws-dash-more {
        margin-left: auto;
        font-size: 0.8125rem;
        color: var(--text-muted);
        text-decoration: none;
    }
    .ws-dash-more:hover { color: var(--accent); }

    .ws-dash-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
    }

    .ws-dash-plate {
        display: inline-block;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 0.1rem 0.4rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        color: var(--text);
        font-family: monospace;
        flex-shrink: 0;
        white-space: nowrap;
    }

    /* Open work order row */
    .ws-dash-order {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid var(--border);
        text-decoration: none;
        color: var(--text);
        transition: background 0.12s;
    }
    .ws-dash-order:last-child { border-bottom: none; }
    .ws-dash-order:hover,
    .ws-dash-order:focus-visible { background: var(--surface-2); outline: none; }
    .ws-dash-order-top {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .ws-dash-order-who {
        font-size: 0.875rem;
        font-weight: 600;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ws-dash-order-vehicle {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 400;
    }
    .ws-dash-order-job {
        font-size: 0.8125rem;
        color: var(--text-muted);
        line-height: 1.4;
    }
    .ws-dash-order-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-top: 0.1rem;
    }
    .ws-dash-order-age {
        font-size: 0.6875rem;
        color: var(--text-faint);
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Status badge — same mapping as work-orders/index.blade.php */
    .wo-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }
    .wo-badge::before {
        content: '';
        width: 5px;
        height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .wo-badge--new         { background: var(--info-dim);    color: var(--info); }
    .wo-badge--new::before         { background: var(--info); }
    .wo-badge--in_progress { background: var(--accent-dim);  color: var(--accent); }
    .wo-badge--in_progress::before { background: var(--accent); }
    .wo-badge--pending      { background: var(--success-dim); color: var(--success); }
    .wo-badge--pending::before      { background: var(--success); }
    .wo-badge--default      { background: var(--surface-2);   color: var(--text-muted); }
    .wo-badge--default::before      { background: var(--text-faint); }
    .wo-badge--blocking     { background: var(--danger-dim);  color: var(--danger); }
    .wo-badge--blocking::before     { background: var(--danger); }

    /* Appointment row */
    .ws-dash-appt {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border);
    }
    .ws-dash-appt:last-child { border-bottom: none; }
    .ws-dash-appt-time {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--accent);
        flex-shrink: 0;
        min-width: 40px;
    }
    .ws-dash-appt-body { min-width: 0; flex: 1; }
    .ws-dash-appt-who {
        font-size: 0.8125rem;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ws-dash-appt-what {
        font-size: 0.75rem;
        color: var(--text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* KTEO row — same badge mapping as kteo/index.blade.php */
    .ws-dash-kteo {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border);
    }
    .ws-dash-kteo:last-child { border-bottom: none; }
    .ws-dash-kteo-body { flex: 1; min-width: 0; }
    .ws-dash-kteo-who {
        font-size: 0.8125rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ws-dash-kteo-date { font-size: 0.75rem; color: var(--text-muted); }
    .kt-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .kt-badge::before {
        content: '';
        width: 5px;
        height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .kt-badge--expired { background: var(--danger-dim); color: var(--danger); }
    .kt-badge--expired::before { background: var(--danger); }
    .kt-badge--soon { background: var(--accent-dim); color: var(--accent); }
    .kt-badge--soon::before { background: var(--accent); }
    .ws-dash-kteo-contact { display: flex; gap: 0.35rem; flex-shrink: 0; }
    .ws-dash-icon-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--text-muted);
        background: var(--surface-2);
    }
    .ws-dash-icon-btn:hover { border-color: var(--accent); color: var(--accent); }
    .ws-dash-icon-btn svg { width: 15px; height: 15px; stroke-width: 2; }

    .ws-dash-empty {
        padding: 1.5rem 1rem;
        font-size: 0.8125rem;
        color: var(--text-muted);
        text-align: center;
    }
    .ws-dash-empty a { color: var(--accent); font-weight: 600; }

    /* ── Dashboard two-column shell (mockup: desktop two columns,
       tablet/mobile stacked) ───────────────────────────────────── */
    .ws-dash-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.75rem;
        align-items: start;
    }
    .ws-dash-main,
    .ws-dash-side {
        display: flex;
        flex-direction: column;
        gap: 1.75rem;
        min-width: 0;
    }

    /* ── Desktop ────────────────────────────────────────────────── */
    @media (min-width: 1024px) {
        .ws-dash-grid {
            grid-template-columns: minmax(0, 1fr) 440px;
        }
    }

    @media (min-width: 768px) {
        .ws-dash-page-title {
            font-size: 2rem;
        }

        .ws-search-input {
            height: 52px;
            font-size: 1rem;
        }

        .ws-actions {
            gap: 0.875rem;
        }
        .ws-card {
            min-height: 132px;
            padding: 1.25rem;
            gap: 0.875rem;
        }
        .ws-card-icon {
            width: 44px;
            height: 44px;
        }
        .ws-card-icon svg {
            width: 24px;
            height: 24px;
        }
        .ws-card-title {
            font-size: 0.9375rem;
        }
        .ws-card-sub {
            font-size: 0.8125rem;
        }

        .ws-dash-order { flex-direction: row; align-items: center; }
        .ws-dash-order-top { flex-direction: column-reverse; align-items: flex-start; flex-shrink: 0; width: 96px; gap: 0.375rem; }
        .ws-dash-order-body { flex: 1; min-width: 0; }
        .ws-dash-order-meta { flex-shrink: 0; flex-direction: column; align-items: flex-end; gap: 0.375rem; margin-top: 0; margin-left: 0.75rem; }
    }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="ws-dash-page-header">
    <div>
        <div class="ws-greeting-date" style="font-size:0.75rem;color:var(--text-faint);margin-bottom:0.2rem;">
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
        <h1 class="ws-dash-page-title">Αρχική</h1>
    </div>
</div>

{{-- Quick search — reuses the existing workshop.search route/controller --}}
<form action="{{ route('workshop.search') }}" method="GET" class="ws-search-wrap">
    <svg class="ws-search-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607 0A7.5 7.5 0 0 1 5.196 15.803"/>
    </svg>
    <input
        type="search"
        name="q"
        class="ws-search-input"
        placeholder="Πινακίδα, πελάτης, τηλέφωνο…"
        autocomplete="off"
        autocorrect="off"
        spellcheck="false"
        value="{{ request('q') }}"
    >
</form>

<div class="ws-gap"></div>

{{-- Two-column shell: desktop = side-by-side, tablet/mobile = stacked --}}
<div class="ws-dash-grid">

    {{-- Main column: open work orders --}}
    <div class="ws-dash-main">
        <section>
            <div class="ws-dash-col-head">
                <h2>Ανοιχτές εντολές</h2>
                <span class="ws-dash-count">{{ $openWorkOrders }}</span>
                <a href="{{ route('workshop.work-orders.index') }}" class="ws-dash-more">Όλες οι εργασίες →</a>
            </div>
            <div class="ws-dash-panel">
                @forelse($recentWorkOrders as $wo)
                    @php
                        $statusMap = [
                            'new'         => ['label' => 'Νέα',        'class' => 'new'],
                            'in_progress' => ['label' => 'Σε εξέλιξη', 'class' => 'in_progress'],
                            'pending'     => ['label' => 'Αναμονή',    'class' => 'pending'],
                        ];
                        $statusInfo = $statusMap[$wo->status] ?? ['label' => ucfirst($wo->status ?? '-'), 'class' => 'default'];
                        $blockingReasonMap = [
                            'waiting_parts' => 'Αναμονή ανταλλακτικού',
                            'waiting_customer_approval' => 'Αναμονή έγκρισης πελάτη',
                            'other' => 'Άλλος λόγος',
                        ];
                        $blockingReasonLabel = $blockingReasonMap[$wo->blocking_reason] ?? null;
                    @endphp
                    <a href="{{ route('workshop.work-orders.show', $wo) }}" class="ws-dash-order">
                        <span class="ws-dash-order-top">
                            <span class="ws-dash-plate">{{ $wo->vehicle->plate_number ?? '—' }}</span>
                        </span>
                        <span class="ws-dash-order-body">
                            <span class="ws-dash-order-who">
                                {{ $wo->customer?->full_name ?? '—' }}
                                <span class="ws-dash-order-vehicle">
                                    {{ trim(($wo->vehicle->make ?? '') . ' ' . ($wo->vehicle->model ?? '')) ?: '—' }}
                                </span>
                            </span>
                            @if($wo->problem_description)
                                <span class="ws-dash-order-job">{{ \Illuminate\Support\Str::limit($wo->problem_description, 70) }}</span>
                            @endif
                        </span>
                        <span class="ws-dash-order-meta">
                            <span class="wo-badge wo-badge--{{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                            @if($blockingReasonLabel)
                                <span class="wo-badge wo-badge--blocking">{{ $blockingReasonLabel }}</span>
                            @endif
                            <span class="ws-dash-order-age">{{ $wo->created_at?->diffForHumans() }}</span>
                        </span>
                    </a>
                @empty
                    <div class="ws-dash-empty">Δεν υπάρχουν ανοιχτές εντολές.</div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Side column: today's appointments, KTEO, quick actions --}}
    <div class="ws-dash-side">

        <section>
            <div class="ws-dash-col-head">
                <h2>Ραντεβού σήμερα</h2>
                <span class="ws-dash-count">{{ $todayAppointments }}</span>
                <a href="{{ route('workshop.appointments.index') }}" class="ws-dash-more">Ημερολόγιο →</a>
            </div>
            <div class="ws-dash-panel">
                @forelse($todaysAppointments as $appointment)
                    <div class="ws-dash-appt">
                        <span class="ws-dash-appt-time">{{ $appointment->appointment_date->format('H:i') }}</span>
                        <span class="ws-dash-plate">{{ $appointment->vehicle->plate_number ?? '—' }}</span>
                        <span class="ws-dash-appt-body">
                            <span class="ws-dash-appt-who">{{ $appointment->customer?->full_name ?? '—' }}</span>
                            <span class="ws-dash-appt-what">
                                {{ $appointment->description ?: trim(($appointment->vehicle->make ?? '') . ' ' . ($appointment->vehicle->model ?? '')) ?: '—' }}
                            </span>
                        </span>
                    </div>
                @empty
                    <div class="ws-dash-empty">
                        Δεν υπάρχει ραντεβού σήμερα.
                        <a href="{{ route('workshop.appointments.create') }}">+ Νέο ραντεβού</a>
                    </div>
                @endforelse
            </div>
        </section>

        <section>
            <div class="ws-dash-col-head">
                <h2>ΚΤΕΟ εντός 30 ημερών</h2>
                <span class="ws-dash-count">{{ $kteoExpiring }}</span>
                <a href="{{ route('workshop.kteo') }}" class="ws-dash-more">Όλα →</a>
            </div>
            <div class="ws-dash-panel">
                @forelse($expiringVehicles as $vehicle)
                    @php
                        $daysLeft = now()->startOfDay()->diffInDays($vehicle->kteo_expires_at, false);
                        $isExpired = $daysLeft < 0;
                        $phone = $vehicle->customer?->phone;
                    @endphp
                    <div class="ws-dash-kteo">
                        <span class="ws-dash-plate">{{ $vehicle->plate_number ?? '—' }}</span>
                        <span class="ws-dash-kteo-body">
                            <span class="ws-dash-kteo-who">{{ $vehicle->customer?->full_name ?? '—' }}</span>
                            <span class="ws-dash-kteo-date">λήγει {{ $vehicle->kteo_expires_at->format('d/m') }}</span>
                        </span>
                        @if($isExpired)
                            <span class="kt-badge kt-badge--expired">Ληγμένο</span>
                        @else
                            <span class="kt-badge kt-badge--soon">{{ $daysLeft }} ημ.</span>
                        @endif
                        @if($phone)
                            <span class="ws-dash-kteo-contact">
                                <a class="ws-dash-icon-btn" href="tel:{{ $phone }}" title="Κλήση">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                                    </svg>
                                </a>
                                <a class="ws-dash-icon-btn" href="sms:{{ $phone }}" title="SMS">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                                    </svg>
                                </a>
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="ws-dash-empty">Δεν υπάρχουν λήξεις ΚΤΕΟ εντός 30 ημερών.</div>
                @endforelse
            </div>
        </section>

        <section>
            <div class="ws-label">Ενέργειες</div>
            <div class="ws-actions">
                <a href="{{ route('workshop.work-orders.create') }}" class="ws-card ws-card--primary">
                    <div class="ws-card-icon ws-card-icon--amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                    </div>
                    <div class="ws-card-body">
                        <div class="ws-card-title">Νέα εντολή</div>
                        <div class="ws-card-sub">Άνοιγμα εντολής</div>
                    </div>
                </a>

                <a href="{{ route('workshop.customers.index') }}" class="ws-card">
                    <div class="ws-card-icon ws-card-icon--green">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
                        </svg>
                    </div>
                    <div class="ws-card-body">
                        <div class="ws-card-title">Πελάτες</div>
                        <div class="ws-card-sub">Αναζήτηση</div>
                    </div>
                </a>

                <a href="{{ route('workshop.vehicles.create') }}" class="ws-card">
                    <div class="ws-card-icon ws-card-icon--gray">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                        </svg>
                    </div>
                    <div class="ws-card-body">
                        <div class="ws-card-title">Νέο όχημα</div>
                        <div class="ws-card-sub">Καταχώρηση</div>
                    </div>
                </a>
            </div>
        </section>

    </div>

</div>

@endsection
