@extends('layouts.workshop')

@section('title', 'Επεξεργασία Οχήματος — Συνεργείο')
@section('header-title', 'Επεξεργασία Οχήματος')

@push('styles')
<style>
    /* ── Toolbar ─────────────────────────────────────────────── */
    .woc-toolbar {
        display: flex;
        align-items: center;
        padding: 0.25rem 0 1.25rem;
    }
    .woc-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--text-muted);
        padding: 0.4rem 0.875rem 0.4rem 0.625rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .woc-back-btn:hover {
        color: var(--text);
        border-color: var(--text-faint);
        background: var(--surface-2);
    }
    .woc-back-btn svg { width: 14px; height: 14px; stroke-width: 2.5; }

    /* ── Page title ──────────────────────────────────────────── */
    .woc-page-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
        margin-bottom: 1.375rem;
    }

    /* ── Form card ───────────────────────────────────────────── */
    .woc-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 0.875rem;
    }
    .woc-card-title {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--text-faint);
        padding: 0.75rem 1.125rem 0.5rem;
        border-bottom: 1px solid var(--border);
    }
    .woc-card-body {
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* ── Field ───────────────────────────────────────────────── */
    .woc-field {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
    }
    .woc-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-muted);
    }
    .woc-label .woc-req { color: var(--danger); margin-left: 2px; }

    .woc-input,
    .woc-select {
        width: 100%;
        background: var(--surface-3);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.65rem 0.875rem;
        font-size: 0.9375rem;
        color: var(--text);
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        -webkit-appearance: none;
        font-family: inherit;
    }
    .woc-select {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%238b949e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.5rem;
    }
    .woc-input::placeholder { color: var(--text-faint); }
    .woc-input:focus,
    .woc-select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .woc-input.is-invalid,
    .woc-select.is-invalid {
        border-color: var(--danger);
        box-shadow: 0 0 0 3px rgba(248,81,73,0.12);
    }
    .woc-error {
        font-size: 0.75rem;
        color: var(--danger);
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .woc-error::before {
        content: '!';
        display: inline-flex;
        width: 14px; height: 14px;
        background: var(--danger);
        color: #fff;
        font-size: 0.625rem;
        font-weight: 800;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* ── Validation alert ────────────────────────────────────── */
    .woc-alert {
        background: var(--danger-dim);
        border: 1px solid rgba(248,81,73,0.3);
        border-radius: var(--radius-sm);
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.8125rem;
        color: var(--danger);
    }
    .woc-alert ul { margin: 0.375rem 0 0 1rem; }
    .woc-alert li { margin-bottom: 0.2rem; }

    /* ── Success flash ───────────────────────────────────────── */
    .woc-success {
        display:flex;align-items:center;gap:0.625rem;
        background:var(--success-dim);border:1px solid rgba(63,185,80,0.3);
        border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1rem;
        font-size:0.875rem;color:var(--success);font-weight:500;
    }
    .woc-success svg { width: 16px; height: 16px; flex-shrink: 0; }

    /* ── Submit row ──────────────────────────────────────────── */
    .woc-submit-row {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        padding-top: 0.25rem;
    }
    .woc-cancel-btn {
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-muted);
        padding: 0.55rem 1.125rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .woc-cancel-btn:hover { color: var(--text); border-color: var(--text-faint); background: var(--surface-2); }
    .woc-submit-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: #0d1117;
        background: var(--accent);
        border: none;
        border-radius: var(--radius-sm);
        padding: 0.55rem 1.375rem;
        cursor: pointer;
        transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
        box-shadow: 0 1px 6px rgba(245,158,11,0.25);
    }
    .woc-submit-btn:hover { background: #fbbf24; box-shadow: 0 2px 10px rgba(245,158,11,0.4); }
    .woc-submit-btn:active { transform: scale(0.98); }
    .woc-submit-btn svg { width: 15px; height: 15px; stroke-width: 2.5; }

    /* ── Desktop ─────────────────────────────────────────────── */
    @media (min-width: 768px) {
        .woc-page-title { font-size: 2rem; }
        .woc-card-body { padding: 1.25rem 1.375rem; }
        .woc-card-body--grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        .woc-card-body--grid .span2 { grid-column: span 2; }
    }
</style>
@endpush

@section('content')

<div class="woc-toolbar">
    <a href="{{ route('workshop.dashboard') }}" class="woc-back-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
        </svg>
        Αρχική
    </a>
</div>

<h1 class="woc-page-title">Επεξεργασία Οχήματος — {{ $vehicle->plate_number ?? '—' }}</h1>

@if(session('success'))
    <div class="woc-success">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="woc-alert">
        <strong>Διόρθωσε τα παρακάτω πεδία:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('workshop.vehicles.update', $vehicle) }}" novalidate>
    @csrf
    @method('PUT')

    @include('workshop.vehicles._fields', ['vehicle' => $vehicle])

    <div class="woc-submit-row">
        <a href="{{ route('workshop.dashboard') }}" class="woc-cancel-btn">Άκυρο</a>
        <button type="submit" class="woc-submit-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            Αποθήκευση
        </button>
    </div>

</form>
@endsection
