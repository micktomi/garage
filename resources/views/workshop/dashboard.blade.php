@extends('layouts.workshop')

@section('title', 'Αρχική — Συνεργείο')
@section('header-title', 'Αρχική')

@push('styles')
<style>
    /* ── Greeting ───────────────────────────────────────────────── */
    .ws-greeting {
        padding: 0.125rem 0 1rem;
    }
    .ws-greeting-date {
        font-size: 0.75rem;
        color: var(--text-faint);
        letter-spacing: 0.02em;
        margin-bottom: 0.2rem;
    }
    .ws-greeting-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: 0;
        color: var(--text);
        line-height: 1.2;
    }
    .ws-greeting-title span {
        color: var(--accent);
    }

    /* ── Stat cards row ─────────────────────────────────────────── */
    .ws-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.5rem;
    }

    .ws-stat {
        background: var(--surface);
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 0.75rem 0.7rem;
        display: flex;
        flex-direction: column;
        gap: 0.22rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--highlight), var(--shadow-sm);
    }
    .ws-stat::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 12px 12px 0 0;
        opacity: 0.9;
    }
    .ws-stat.ws-stat--amber::before { background: var(--accent); }
    .ws-stat.ws-stat--blue::before  { background: var(--info); }
    .ws-stat.ws-stat--red::before   { background: var(--danger); }
    .ws-stat.ws-stat--green::before { background: var(--success); }

    .ws-stat-num {
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .ws-stat--amber .ws-stat-num { color: var(--accent); }
    .ws-stat--blue  .ws-stat-num { color: var(--info); }
    .ws-stat--red   .ws-stat-num { color: var(--danger); }
    .ws-stat--green .ws-stat-num { color: var(--success); }

    .ws-stat-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--text-muted);
        line-height: 1.3;
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
        grid-template-columns: 1fr 1fr;
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
    .ws-card-icon--blue  { background: var(--info-dim);    color: var(--info); }
    .ws-card-icon--green { background: var(--success-dim); color: var(--success); }
    .ws-card-icon--red   { background: var(--danger-dim);  color: var(--danger); }
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

    /* Full-width card variant */
    .ws-card--wide {
        grid-column: span 2;
        flex-direction: row;
        align-items: center;
        gap: 0.875rem;
        min-height: auto;
        padding: 0.875rem 1rem;
    }
    .ws-card--wide .ws-card-body { flex: 1; }
    .ws-card--wide .ws-card-icon { width: 40px; height: 40px; flex-shrink: 0; }
    .ws-card--wide .ws-card-icon svg { width: 22px; height: 22px; }

    .ws-card-arrow {
        color: var(--text-faint);
        flex-shrink: 0;
    }
    .ws-card-arrow svg { width: 16px; height: 16px; stroke-width: 2; }

    .ws-card--kteo {
        border-color: var(--border-soft);
        background: rgba(255,255,255,0.045);
    }
    .ws-card--kteo-alert {
        background: rgba(248, 81, 73, 0.10);
        border-color: rgba(248, 81, 73, 0.30);
    }
    .ws-card--kteo-alert .ws-card-title {
        color: #fee2e2;
    }

    /* ── Desktop ────────────────────────────────────────────────── */
    @media (min-width: 768px) {
        .ws-greeting {
            padding: 0.35rem 0 1.7rem;
        }
        .ws-greeting-title {
            font-size: 2rem;
        }

        .ws-stats {
            gap: 0.875rem;
        }
        .ws-stat {
            padding: 1rem 1.125rem;
        }
        .ws-stat-num {
            font-size: 2.15rem;
        }
        .ws-stat-label {
            font-size: 0.75rem;
        }

        .ws-search-input {
            height: 52px;
            font-size: 1rem;
        }

        .ws-actions {
            grid-template-columns: repeat(3, 1fr);
            gap: 0.875rem;
        }
        .ws-card--wide {
            grid-column: span 3;
            padding: 1.125rem 1.375rem;
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
    }
</style>
@endpush

@section('content')

{{-- Greeting --}}
<div class="ws-greeting">
    <div class="ws-greeting-date">{{ now()->translatedFormat('l, d F Y') }}</div>
    <h1 class="ws-greeting-title">
        Καλημέρα,<br>
        <span>τι τρέχει σήμερα;</span>
    </h1>
</div>

{{-- Stats --}}
<div class="ws-label">Επισκόπηση</div>
<div class="ws-stats">
    <div class="ws-stat ws-stat--amber">
        <div class="ws-stat-num">{{ $openWorkOrders }}</div>
        <div class="ws-stat-label">Ανοιχτές εντολές</div>
    </div>
    <div class="ws-stat ws-stat--blue">
        <div class="ws-stat-num">{{ $todayAppointments }}</div>
        <div class="ws-stat-label">Ραντεβού σήμερα</div>
    </div>
    <div class="ws-stat {{ $kteoExpiring > 0 ? 'ws-stat--red' : 'ws-stat--green' }}">
        <div class="ws-stat-num">{{ $kteoExpiring }}</div>
        <div class="ws-stat-label">ΚΤΕΟ 30 ημερών</div>
    </div>
</div>

<div class="ws-gap"></div>

{{-- Quick search --}}
<div class="ws-label">Γρήγορη αναζήτηση</div>
<div class="ws-search-wrap">
    <svg class="ws-search-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607 0A7.5 7.5 0 0 1 5.196 15.803"/>
    </svg>
    <input
        type="search"
        class="ws-search-input"
        placeholder="Πινακίδα, πελάτης, τηλέφωνο…"
        autocomplete="off"
        autocorrect="off"
        spellcheck="false"
        disabled
    >
</div>

<div class="ws-gap"></div>

{{-- Action cards --}}
<div class="ws-label">Ενέργειες</div>
<div class="ws-actions">

    {{-- Αναζήτηση πινακίδας — disabled, ανεπτυγμένο αργότερα --}}
    <a href="#" class="ws-card ws-card--disabled">
        <div class="ws-card-icon ws-card-icon--gray">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607 0A7.5 7.5 0 0 1 5.196 15.803"/>
            </svg>
        </div>
        <div class="ws-card-body">
            <div class="ws-card-title">Αναζήτηση πινακίδας</div>
            <div class="ws-card-sub">Σύντομα</div>
        </div>
    </a>

    {{-- Νέα εντολή εργασίας --}}
    <a href="{{ route('workshop.work-orders.create') }}" class="ws-card ws-card--primary">
        <div class="ws-card-icon ws-card-icon--amber">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
        </div>
        <div class="ws-card-body">
            <div class="ws-card-title">Νέα εντολή εργασίας</div>
            <div class="ws-card-sub">Άνοιγμα εντολής</div>
        </div>
    </a>

    {{-- Ανοιχτές εργασίες --}}
    <a href="{{ route('workshop.work-orders.index') }}" class="ws-card">
        <div class="ws-card-icon ws-card-icon--blue">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
            </svg>
        </div>
        <div class="ws-card-body">
            <div class="ws-card-title">Ανοιχτές εργασίες</div>
            <div class="ws-card-sub">{{ $openWorkOrders }} σε εξέλιξη</div>
        </div>
    </a>

    {{-- Πελάτες --}}
    <a href="{{ route('workshop.customers.index') }}" class="ws-card">
        <div class="ws-card-icon ws-card-icon--green">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
            </svg>
        </div>
        <div class="ws-card-body">
            <div class="ws-card-title">Πελάτες</div>
            <div class="ws-card-sub">Αναζήτηση και καταχώρηση</div>
        </div>
    </a>

    {{-- Νέο όχημα --}}
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

    {{-- Υπενθυμίσεις ΚΤΕΟ — full width --}}
    <a href="{{ route('workshop.kteo') }}" class="ws-card ws-card--wide ws-card--kteo {{ $kteoExpiring > 0 ? 'ws-card--kteo-alert' : '' }}">
        <div class="ws-card-icon ws-card-icon--{{ $kteoExpiring > 0 ? 'red' : 'gray' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
        </div>
        <div class="ws-card-body">
            <div class="ws-card-title">Υπενθυμίσεις ΚΤΕΟ</div>
            <div class="ws-card-sub">
                @if($kteoExpiring > 0)
                    {{ $kteoExpiring }} οχήματα λήγουν εντός 30 ημερών
                @else
                    Δεν υπάρχουν επείγοντες λήξεις
                @endif
            </div>
        </div>
        <span class="ws-card-arrow">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </span>
    </a>

</div>

@endsection
