@extends('layouts.workshop')

@section('title', 'Εντολή #' . str_pad($workOrder->id, 4, '0', STR_PAD_LEFT) . ' — Συνεργείο')
@section('header-title', 'Εντολή #' . str_pad($workOrder->id, 4, '0', STR_PAD_LEFT))

@push('styles')
<style>
    /* ── Top toolbar ─────────────────────────────────────────── */
    .wos-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.25rem 0 1.25rem;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .wos-back-btn {
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
    .wos-back-btn:hover {
        color: var(--text);
        border-color: var(--text-faint);
        background: var(--surface-2);
    }
    .wos-back-btn svg { width: 14px; height: 14px; stroke-width: 2.5; }

    .wos-print-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-muted);
        padding: 0.4rem 0.875rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .wos-print-btn:hover {
        color: var(--text);
        border-color: var(--text-faint);
        background: var(--surface-2);
    }
    .wos-print-btn svg { width: 14px; height: 14px; stroke-width: 2; }

    /* ── Status badge ────────────────────────────────────────── */
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
    .wo-badge--new       { background: var(--info-dim);     color: var(--info);    }
    .wo-badge--new::before       { background: var(--info); }
    .wo-badge--in_progress { background: var(--accent-dim);  color: var(--accent); }
    .wo-badge--in_progress::before { background: var(--accent); }
    .wo-badge--pending   { background: var(--success-dim);  color: var(--success); }
    .wo-badge--pending::before   { background: var(--success); }
    .wo-badge--completed { background: var(--success-dim);  color: var(--success); }
    .wo-badge--completed::before { background: var(--success); }
    .wo-badge--cancelled { background: var(--danger-dim);   color: var(--danger); }
    .wo-badge--cancelled::before { background: var(--danger); }
    .wo-badge--default   { background: var(--surface-2);    color: var(--text-muted); }
    .wo-badge--default::before   { background: var(--text-faint); }

    /* ── Info sections ───────────────────────────────────────── */
    .wos-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 0.875rem;
        overflow: hidden;
    }
    .wos-section-title {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--text-faint);
        padding: 0.75rem 1.125rem 0.5rem;
        border-bottom: 1px solid var(--border);
    }

    .wos-rows {
        display: flex;
        flex-direction: column;
    }
    .wos-row {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        padding: 0.6rem 1.125rem;
        border-bottom: 1px solid var(--border);
    }
    .wos-row:last-child { border-bottom: none; }
    .wos-row-label {
        font-size: 0.75rem;
        color: var(--text-faint);
        font-weight: 500;
        flex-shrink: 0;
        min-width: 110px;
    }
    .wos-row-value {
        font-size: 0.875rem;
        color: var(--text);
        font-weight: 500;
        flex: 1;
        word-break: break-word;
    }
    .wos-row-value--plate {
        display: inline-block;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 0.1rem 0.45rem;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        font-family: monospace;
    }
    .wos-row-value--accent {
        color: var(--accent);
        font-weight: 700;
    }

    /* ── Parts table ─────────────────────────────────────────── */
    .wos-parts-table {
        width: 100%;
        border-collapse: collapse;
    }
    .wos-parts-table th {
        font-size: 0.625rem;
        font-weight: 600;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--text-faint);
        padding: 0.5rem 1.125rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }
    .wos-parts-table th:last-child,
    .wos-parts-table td:last-child { text-align: right; }
    .wos-parts-table td {
        font-size: 0.8125rem;
        color: var(--text);
        padding: 0.625rem 1.125rem;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }
    .wos-parts-table tr:last-child td { border-bottom: none; }
    .wos-parts-table td:nth-child(2),
    .wos-parts-table td:nth-child(3) {
        color: var(--text-muted);
    }
    .wos-parts-empty {
        padding: 1.5rem 1.125rem;
        font-size: 0.875rem;
        color: var(--text-muted);
        text-align: center;
    }

    /* ── Totals ──────────────────────────────────────────────── */
    .wos-totals {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .wos-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem 1.125rem;
        border-bottom: 1px solid var(--border);
        font-size: 0.875rem;
    }
    .wos-total-row:last-child { border-bottom: none; }
    .wos-total-label { color: var(--text-muted); font-weight: 500; }
    .wos-total-value { font-weight: 600; color: var(--text); }
    .wos-total-row--grand .wos-total-label { color: var(--text); font-weight: 700; font-size: 0.9375rem; }
    .wos-total-row--grand .wos-total-value { color: var(--accent); font-weight: 800; font-size: 1.0625rem; }

    /* Desktop */
    @media (min-width: 768px) {
        .wos-rows { flex-direction: row; flex-wrap: wrap; }
        .wos-row {
            flex-direction: column;
            gap: 0.15rem;
            padding: 0.875rem 1.375rem;
            border-bottom: none;
            border-right: 1px solid var(--border);
            min-width: 140px;
            flex: 1;
        }
        .wos-row:last-child { border-right: none; }
        .wos-row-label { min-width: unset; }

        .wos-parts-table th,
        .wos-parts-table td { padding: 0.625rem 1.375rem; }

        .wos-total-row { padding: 0.6rem 1.375rem; }
    }

    /* ── Status change buttons ────────────────────────────────── */
    .wos-status-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 0.875rem;
        overflow: hidden;
    }
    .wos-status-title {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--text-faint);
        padding: 0.75rem 1.125rem 0.5rem;
        border-bottom: 1px solid var(--border);
    }
    .wos-status-body {
        padding: 0.875rem 1.125rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .wos-status-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.45rem 1rem;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: none;
        color: var(--text-muted);
        cursor: pointer;
        transition: background 0.13s, border-color 0.13s, color 0.13s, transform 0.1s;
        -webkit-tap-highlight-color: transparent;
    }
    .wos-status-btn::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        opacity: 0.5;
        flex-shrink: 0;
    }
    .wos-status-btn:hover:not(.is-current) {
        border-color: var(--text-faint);
        color: var(--text);
    }
    .wos-status-btn:active { transform: scale(0.97); }

    /* per-status colours for active state */
    .wos-status-btn--new.is-current       { background: var(--info-dim);     border-color: rgba(88,166,255,.4);  color: var(--info); }
    .wos-status-btn--in_progress.is-current { background: var(--accent-dim); border-color: rgba(245,158,11,.4); color: var(--accent); }
    .wos-status-btn--completed.is-current  { background: var(--success-dim); border-color: rgba(63,185,80,.4);  color: var(--success); }
    .wos-status-btn--cancelled.is-current  { background: var(--danger-dim);  border-color: rgba(248,81,73,.4);  color: var(--danger); }

    /* disabled look for current (not a submit) */
    .wos-status-btn.is-current { cursor: default; pointer-events: none; }
    .wos-status-btn.is-current::before { opacity: 1; }

    @media (min-width: 768px) {
        .wos-status-body { padding: 0.875rem 1.375rem; gap: 0.625rem; }
        .wos-status-btn  { font-size: 0.875rem; padding: 0.5rem 1.125rem; }
    }
</style>
@endpush

@section('content')

{{-- Success flash --}}
@if(session('success'))
    <div style="
        display:flex;align-items:center;gap:0.625rem;
        background:var(--success-dim);border:1px solid rgba(63,185,80,0.3);
        border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1rem;
        font-size:0.875rem;color:var(--success);font-weight:500;
    ">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

@php
    $statusMap = [
        'new'         => ['label' => 'Νέα',         'class' => 'new'],
        'in_progress' => ['label' => 'Σε εξέλιξη',  'class' => 'in_progress'],
        'pending'     => ['label' => 'Αναμονή',     'class' => 'pending'],
        'completed'   => ['label' => 'Ολοκληρώθηκε','class' => 'completed'],
        'cancelled'   => ['label' => 'Ακυρώθηκε',   'class' => 'cancelled'],
    ];
    $statusInfo = $statusMap[$workOrder->status] ?? ['label' => ucfirst($workOrder->status ?? '-'), 'class' => 'default'];
@endphp

{{-- Toolbar --}}
<div class="wos-toolbar">
    <a href="{{ route('workshop.work-orders.index') }}" class="wos-back-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
        </svg>
        Πίσω
    </a>
    <div style="display:flex;align-items:center;gap:0.5rem;">
        <span class="wo-badge wo-badge--{{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
        <a href="{{ route('work-orders.print', $workOrder) }}" target="_blank" class="wos-print-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
            </svg>
            Εκτύπωση
        </a>
    </div>
</div>

{{-- ── Status change ─────────────────────────────────────────── --}}
@php
    $statusButtons = [
        'new'         => ['label' => 'Νέα',           'cls' => 'new'],
        'in_progress' => ['label' => 'Σε εξέλιξη',    'cls' => 'in_progress'],
        'completed'   => ['label' => 'Ολοκληρωμένη',  'cls' => 'completed'],
        'cancelled'   => ['label' => 'Ακυρωμένη',     'cls' => 'cancelled'],
    ];
@endphp
<div class="wos-status-section">
    <div class="wos-status-title">Αλλαγή Κατάστασης</div>
    <div class="wos-status-body">
        @foreach($statusButtons as $value => $btn)
            @if($value === $workOrder->status)
                <span class="wos-status-btn wos-status-btn--{{ $btn['cls'] }} is-current">
                    {{ $btn['label'] }}
                </span>
            @else
                <form method="POST"
                      action="{{ route('workshop.work-orders.status', $workOrder) }}"
                      style="display:contents">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $value }}">
                    <button type="submit" class="wos-status-btn wos-status-btn--{{ $btn['cls'] }}">
                        {{ $btn['label'] }}
                    </button>
                </form>
            @endif
        @endforeach
    </div>
</div>

{{-- Customer & Vehicle --}}
<div class="wos-section">
    <div class="wos-section-title">Πελάτης &amp; Όχημα</div>
    <div class="wos-rows">
        <div class="wos-row">
            <span class="wos-row-label">Πελάτης</span>
            <span class="wos-row-value">{{ $workOrder->customer?->full_name ?? '—' }}</span>
        </div>
        <div class="wos-row">
            <span class="wos-row-label">Τηλέφωνο</span>
            <span class="wos-row-value">
                @if($workOrder->customer?->phone)
                    <a href="tel:{{ $workOrder->customer->phone }}" style="color:var(--info)">{{ $workOrder->customer->phone }}</a>
                @else
                    —
                @endif
            </span>
        </div>
        <div class="wos-row">
            <span class="wos-row-label">Όχημα</span>
            <span class="wos-row-value">
                {{ trim(($workOrder->vehicle?->make ?? '') . ' ' . ($workOrder->vehicle?->model ?? '')) ?: '—' }}
            </span>
        </div>
        <div class="wos-row">
            <span class="wos-row-label">Πινακίδα</span>
            <span class="wos-row-value">
                @if($workOrder->vehicle?->plate_number)
                    <span class="wos-row-value--plate">{{ $workOrder->vehicle->plate_number }}</span>
                @else
                    —
                @endif
            </span>
        </div>
    </div>
</div>

{{-- Work order details --}}
<div class="wos-section">
    <div class="wos-section-title">Στοιχεία Εντολής</div>
    <div class="wos-rows">
        <div class="wos-row">
            <span class="wos-row-label">Αρ. εντολής</span>
            <span class="wos-row-value">#{{ str_pad($workOrder->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="wos-row">
            <span class="wos-row-label">Κατάσταση</span>
            <span class="wos-row-value">
                <span class="wo-badge wo-badge--{{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
            </span>
        </div>
        <div class="wos-row">
            <span class="wos-row-label">Ημερομηνία</span>
            <span class="wos-row-value">{{ $workOrder->created_at?->translatedFormat('d F Y, H:i') ?? '—' }}</span>
        </div>
    </div>

    {{-- Problem description --}}
    @if($workOrder->problem_description)
        <div style="padding:0.75rem 1.125rem;border-top:1px solid var(--border);">
            <div class="wos-row-label" style="margin-bottom:0.35rem;">Πρόβλημα</div>
            <div style="font-size:0.875rem;color:var(--text);line-height:1.6;white-space:pre-line;">{{ $workOrder->problem_description }}</div>
        </div>
    @endif
</div>

{{-- Parts --}}
<div class="wos-section">
    <div class="wos-section-title">Ανταλλακτικά / Εργασία</div>
    @if($workOrder->workOrderParts->isEmpty())
        <div class="wos-parts-empty">Δεν έχουν καταχωρηθεί ανταλλακτικά.</div>
    @else
        <table class="wos-parts-table">
            <thead>
                <tr>
                    <th>Περιγραφή</th>
                    <th>Ποσ.</th>
                    <th>Τιμή</th>
                    <th>Σύνολο</th>
                </tr>
            </thead>
            <tbody>
                @foreach($workOrder->workOrderParts as $wop)
                    @php
                        $lineTotal = $wop->line_total ?? ($wop->quantity * $wop->unit_price);
                    @endphp
                    <tr>
                        <td>{{ $wop->displayName() }}</td>
                        <td>{{ $wop->quantity }}</td>
                        <td>{{ number_format($wop->unit_price ?? 0, 2, ',', '.') }} €</td>
                        <td>{{ number_format($lineTotal, 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Totals --}}
<div class="wos-section">
    <div class="wos-section-title">Κόστος</div>
    <div class="wos-totals">
        <div class="wos-total-row">
            <span class="wos-total-label">Εργασία</span>
            <span class="wos-total-value">{{ number_format($workOrder->labor_cost ?? 0, 2, ',', '.') }} €</span>
        </div>
        <div class="wos-total-row">
            <span class="wos-total-label">Ανταλλακτικά</span>
            <span class="wos-total-value">{{ number_format($workOrder->parts_cost ?? 0, 2, ',', '.') }} €</span>
        </div>
        <div class="wos-total-row wos-total-row--grand">
            <span class="wos-total-label">Σύνολο</span>
            <span class="wos-total-value">{{ number_format($workOrder->total_cost ?? 0, 2, ',', '.') }} €</span>
        </div>
    </div>
</div>

@endsection
