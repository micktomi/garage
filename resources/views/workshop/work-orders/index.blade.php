@extends('layouts.workshop')

@section('title', 'Εντολές Εργασίας — Συνεργείο')
@section('header-title', 'Εντολές Εργασίας')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────────────────── */
    .wo-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.25rem 0 1.25rem;
    }
    .wo-page-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
    }
    .wo-count-badge {
        background: var(--accent-dim);
        color: var(--accent);
        border: 1px solid rgba(245,158,11,0.25);
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
    }

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
    .wo-badge--default   { background: var(--surface-2);    color: var(--text-muted); }
    .wo-badge--default::before   { background: var(--text-faint); }
    .wo-badge--blocking  { background: var(--danger-dim);   color: var(--danger); }
    .wo-badge--blocking::before  { background: var(--danger); }

    /* ── Work order card ─────────────────────────────────────── */
    .wo-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .wo-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        transition: border-color 0.15s, background 0.15s;
        text-decoration: none;
        color: var(--text);
        -webkit-tap-highlight-color: transparent;
    }
    .wo-card:hover,
    .wo-card:focus-visible {
        background: var(--surface-2);
        border-color: #484f58;
        outline: none;
    }
    .wo-card:active { transform: scale(0.99); }

    /* Card top row */
    .wo-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .wo-card-id {
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--text-faint);
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    /* Card main info */
    .wo-card-main {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .wo-card-customer {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.3;
    }
    .wo-card-vehicle {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    .wo-card-plate {
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
    }

    /* Description */
    .wo-card-desc {
        font-size: 0.8125rem;
        color: var(--text-muted);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Divider */
    .wo-card-divider {
        height: 1px;
        background: var(--border);
        margin: 0.125rem 0;
    }

    /* Card footer row */
    .wo-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .wo-card-costs {
        display: flex;
        gap: 1rem;
    }
    .wo-cost-item {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }
    .wo-cost-label {
        font-size: 0.625rem;
        color: var(--text-faint);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .wo-cost-value {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text);
    }
    .wo-cost-value--total {
        color: var(--accent);
    }

    .wo-card-meta {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.6875rem;
        color: var(--text-faint);
    }

    /* Open button */
    .wo-btn-open {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--accent);
        background: var(--accent-dim);
        border: 1px solid rgba(245,158,11,0.25);
        border-radius: var(--radius-sm);
        padding: 0.4rem 0.875rem;
        transition: background 0.15s, border-color 0.15s;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .wo-btn-open:hover {
        background: rgba(245,158,11,0.2);
        border-color: rgba(245,158,11,0.45);
    }
    .wo-btn-open svg { width: 13px; height: 13px; stroke-width: 2.5; }

    /* Empty state */
    .wo-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }
    .wo-empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        color: var(--text-faint);
    }
    .wo-empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.25rem;
    }
    .wo-empty-sub {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    /* Desktop enhancements */
    @media (min-width: 768px) {
        .wo-page-title { font-size: 2rem; }

        .wo-card {
            flex-direction: row;
            align-items: center;
            gap: 1.25rem;
            padding: 1.125rem 1.375rem;
        }
        .wo-card-top { flex-direction: column-reverse; align-items: flex-start; flex-shrink: 0; width: 100px; }
        .wo-card-main { flex: 1; min-width: 0; }
        .wo-card-divider { display: none; }
        .wo-card-footer { flex-direction: column; align-items: flex-end; flex-shrink: 0; gap: 0.625rem; }
        .wo-card-costs { flex-direction: column; align-items: flex-end; gap: 0.25rem; }
        .wo-cost-item { flex-direction: row; align-items: center; gap: 0.4rem; }
        .wo-cost-label { width: 50px; text-align: right; }
    }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="wo-page-header">
    <div>
        <div class="ws-greeting-date" style="font-size:0.75rem;color:var(--text-faint);margin-bottom:0.2rem;">
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
        <h1 class="wo-page-title">Ανοιχτές Εργασίες</h1>
    </div>
    <span class="wo-count-badge">{{ $workOrders->count() }}</span>
</div>

@if($workOrders->isEmpty())
    <div class="wo-empty">
        <svg class="wo-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
        </svg>
        <div class="wo-empty-title">Δεν υπάρχουν ανοιχτές εντολές</div>
        <div class="wo-empty-sub">Όλα ολοκληρώθηκαν ή δεν έχει ανοίξει κάποια.</div>
    </div>
@else
    <div class="wo-list">
        @foreach($workOrders as $wo)
            @php
                $statusMap = [
                    'new'         => ['label' => 'Νέα',         'class' => 'new'],
                    'in_progress' => ['label' => 'Σε εξέλιξη',  'class' => 'in_progress'],
                    'pending'     => ['label' => 'Αναμονή',     'class' => 'pending'],
                ];
                $statusInfo = $statusMap[$wo->status] ?? ['label' => ucfirst($wo->status ?? '-'), 'class' => 'default'];
                $blockingReasonMap = [
                    'waiting_parts' => 'Αναμονή ανταλλακτικού',
                    'waiting_customer_approval' => 'Αναμονή έγκρισης πελάτη',
                    'other' => 'Άλλος λόγος',
                ];
                $blockingReasonLabel = $blockingReasonMap[$wo->blocking_reason] ?? null;
            @endphp
            <div class="wo-card">
                {{-- ID + status --}}
                <div class="wo-card-top">
                    <span class="wo-card-id">#{{ str_pad($wo->id, 4, '0', STR_PAD_LEFT) }}</span>
                    <span style="display:flex;gap:0.375rem;">
                        <span class="wo-badge wo-badge--{{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                        @if($blockingReasonLabel)
                            <span class="wo-badge wo-badge--blocking">{{ $blockingReasonLabel }}</span>
                        @endif
                    </span>
                </div>

                {{-- Customer + Vehicle --}}
                <div class="wo-card-main">
                    <div class="wo-card-customer">
                        {{ $wo->customer?->full_name ?? '—' }}
                    </div>
                    <div class="wo-card-vehicle">
                        @if($wo->vehicle)
                            <span class="wo-card-plate">{{ $wo->vehicle->plate_number ?? '—' }}</span>
                            @if($wo->vehicle->make || $wo->vehicle->model)
                                <span>{{ trim(($wo->vehicle->make ?? '') . ' ' . ($wo->vehicle->model ?? '')) }}</span>
                            @endif
                        @else
                            <span>—</span>
                        @endif
                    </div>
                    @if($wo->problem_description)
                        <div class="wo-card-desc">{{ $wo->problem_description }}</div>
                    @endif
                </div>

                <div class="wo-card-divider"></div>

                {{-- Costs + button --}}
                <div class="wo-card-footer">
                    <div class="wo-card-costs">
                        <div class="wo-cost-item">
                            <span class="wo-cost-label">Εργασία</span>
                            <span class="wo-cost-value">{{ number_format($wo->labor_cost ?? 0, 2, ',', '.') }} €</span>
                        </div>
                        <div class="wo-cost-item">
                            <span class="wo-cost-label">Ανταλ.</span>
                            <span class="wo-cost-value">{{ number_format($wo->parts_cost ?? 0, 2, ',', '.') }} €</span>
                        </div>
                        <div class="wo-cost-item">
                            <span class="wo-cost-label">Σύνολο</span>
                            <span class="wo-cost-value wo-cost-value--total">{{ number_format($wo->total_cost ?? 0, 2, ',', '.') }} €</span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
                        <span class="wo-card-meta">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            {{ $wo->created_at?->translatedFormat('d M Y') ?? '—' }}
                        </span>
                        <a href="{{ route('workshop.work-orders.show', $wo) }}" class="wo-btn-open">
                            Άνοιγμα
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
