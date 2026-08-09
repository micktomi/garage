@extends('layouts.workshop')

@section('title', 'Εντολή #' . str_pad($workOrder->id, 4, '0', STR_PAD_LEFT) . ' — Συνεργείο')
@section('header-title', 'Εντολή #' . str_pad($workOrder->id, 4, '0', STR_PAD_LEFT))
@section('header-back', route('workshop.work-orders.index'))

@push('styles')
<style>
    .wos-page,
    .wos-layout,
    .wos-panel,
    .wos-copy-block,
    .wos-parts {
        min-width: 0;
    }

    /* ── Header ──────────────────────────────────────────────── */
    .wos-head {
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px 18px;
    }

    .wos-title {
        color: var(--ws-text);
        font-family: var(--ws-font-display);
        font-size: 34px;
        font-weight: 700;
        line-height: 1;
    }

    .wos-actions {
        margin-left: auto;
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .wos-sub {
        margin: 6px 0 22px;
        color: var(--ws-text-muted);
        font-size: 14px;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    /* ── Layout ──────────────────────────────────────────────────
       Two independent stacks, like the donor, so a tall side panel
       never pushes the parts table down the page. */
    .wos-layout {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    .wos-stack {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    .wos-copy-list {
        padding: 18px 20px 20px;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
    }

    .wos-copy {
        margin: 0;
        color: var(--ws-text);
        font-family: var(--ws-font-sans);
        font-size: 14px;
        line-height: 1.6;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .wos-copy--empty {
        color: var(--ws-text-faint);
    }

    .wos-info-link {
        color: var(--ws-text);
        border-bottom: 1px solid var(--ws-border-hover);
    }

    .wos-info-link:hover {
        border-bottom-color: var(--ws-text);
    }

    /* ── Status switcher ─────────────────────────────────────── */
    .wos-status-body {
        padding: 14px 20px 18px;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 8px;
    }

    .wos-status-form {
        display: contents;
    }

    .wos-status-btn {
        width: 100%;
        min-height: 42px;
        padding: 9px 12px;
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 9px;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: var(--ws-card);
        color: var(--ws-text-muted);
        font-family: var(--ws-font-sans);
        font-size: 13.5px;
        font-weight: 500;
        text-align: left;
        cursor: pointer;
        transition: border-color 140ms ease, background-color 140ms ease, color 140ms ease;
    }

    .wos-status-btn::before {
        content: '';
        width: 7px;
        height: 7px;
        flex: 0 0 7px;
        border-radius: 999px;
        background: currentColor;
        opacity: .5;
    }

    .wos-status-btn:hover:not(.is-current) {
        border-color: var(--ws-border-hover);
        background: var(--ws-sunken);
        color: var(--ws-text);
    }

    .wos-status-btn.is-current {
        border-color: var(--ws-status-progress-fg);
        background: var(--ws-status-progress-bg);
        color: var(--ws-status-progress-fg);
        font-weight: 600;
        cursor: default;
    }

    .wos-status-btn--new.is-current {
        border-color: var(--ws-status-new-fg);
        background: var(--ws-status-new-bg);
        color: var(--ws-status-new-fg);
    }

    .wos-status-btn--awaiting_parts.is-current {
        border-color: var(--ws-status-awaiting-fg);
        background: var(--ws-status-awaiting-bg);
        color: var(--ws-status-awaiting-fg);
    }

    .wos-status-btn--ready.is-current {
        border-color: var(--ws-status-ready-fg);
        background: var(--ws-status-ready-bg);
        color: var(--ws-status-ready-fg);
    }

    .wos-status-btn--completed.is-current,
    .wos-status-btn--cancelled.is-current {
        border-color: var(--ws-border-hover);
        background: var(--ws-sunken);
        color: var(--ws-text-muted);
    }

    .wos-status-btn.is-current::before {
        opacity: 1;
    }

    /* ── Closure fields (Completed transition only) ─────────────── */
    .wos-status-form--completion {
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: var(--ws-sunken);
    }

    .wos-closure-label {
        color: var(--ws-text-muted);
        font-family: var(--ws-font-sans);
        font-size: 12px;
        font-weight: 600;
    }

    .wos-closure-select {
        width: 100%;
        min-height: 38px;
        padding: 6px 10px;
        border: 1px solid var(--ws-border);
        border-radius: 6px;
        background: var(--ws-card);
        color: var(--ws-text);
        font-family: var(--ws-font-sans);
        font-size: 13.5px;
    }

    .wos-status-form--completion .wos-status-btn {
        border-color: transparent;
        background: var(--ws-status-ready-bg);
        color: var(--ws-status-ready-fg);
    }

    /* ── Parts ───────────────────────────────────────────────── */
    .wos-parts-table,
    .wos-parts-table tbody,
    .wos-parts-table tr,
    .wos-parts-table td {
        display: block;
        width: 100%;
    }

    .wos-parts-table {
        padding: 16px;
        border-collapse: collapse;
        font-family: var(--ws-font-sans);
    }

    .wos-parts-table thead {
        display: none;
    }

    .wos-parts-table tbody {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
    }

    .wos-parts-table tr {
        overflow: hidden;
        border: 1px solid var(--ws-border);
        border-radius: 10px;
        background: var(--ws-card);
    }

    .wos-parts-table td {
        padding: 10px 12px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        border-bottom: 1px solid var(--ws-border);
        color: var(--ws-text);
        font-size: 13px;
        line-height: 18px;
        text-align: right;
    }

    .wos-parts-table td:first-child {
        padding: 13px 12px;
        display: block;
        text-align: left;
    }

    .wos-parts-table td:last-child {
        border-bottom: 0;
    }

    .wos-part-cell-label {
        flex: 0 0 auto;
        color: var(--ws-text-faint);
        font-size: 10.5px;
        font-weight: 500;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .wos-parts-table td:first-child .wos-part-cell-label {
        margin-bottom: 5px;
        display: block;
    }

    .wos-part-cell-content {
        min-width: 0;
        display: block;
    }

    .wos-part-name,
    .wos-part-description,
    .wos-part-note {
        display: block;
        overflow-wrap: anywhere;
    }

    .wos-part-name {
        font-family: var(--ws-font-sans);
        font-size: 14.5px;
        font-weight: 500;
        line-height: 20px;
    }

    .wos-part-description,
    .wos-part-note {
        margin-top: 2px;
        color: var(--ws-text-faint);
        font-size: 12.5px;
        line-height: 17px;
    }

    .wos-part-source {
        display: inline-flex;
        align-items: center;
        border-radius: 5px;
        padding: 4px 8px;
        background: var(--ws-status-completed-bg);
        color: var(--ws-status-completed-fg);
        font-size: 10.5px;
        font-weight: 600;
        line-height: 14px;
        letter-spacing: .06em;
        text-align: left;
    }

    .wos-part-source--from_stock {
        background: var(--ws-status-progress-bg);
        color: var(--ws-status-progress-fg);
    }

    .wos-part-source--customer_supplied {
        background: var(--ws-status-new-bg);
        color: var(--ws-status-new-fg);
    }

    .wos-part-source--purchased_for_job {
        background: var(--ws-status-awaiting-bg);
        color: var(--ws-status-awaiting-fg);
    }

    .wos-parts-empty {
        min-height: 110px;
        padding: 24px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ws-text-faint);
        font-family: var(--ws-font-sans);
        font-size: 13.5px;
        text-align: center;
    }

    /* ── Totals ──────────────────────────────────────────────── */
    .wos-totals {
        padding: 14px 20px 18px;
        border-top: 1px solid var(--ws-border);
        background: var(--ws-sunken);
    }

    .wos-tot {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 16px;
        padding: 5px 0;
        color: var(--ws-text-muted);
        font-size: 14px;
    }

    .wos-tot-value {
        font-family: var(--ws-font-mono);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .wos-tot--grand {
        margin-top: 8px;
        padding-top: 12px;
        border-top: 1px solid var(--ws-border-hover);
        color: var(--ws-text);
        font-family: var(--ws-font-display);
        font-size: 20px;
        font-weight: 700;
    }

    .wos-tot--grand .wos-tot-value {
        font-size: 20px;
        font-weight: 700;
    }

    @media (min-width: 600px) {
        .wos-copy-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .wos-copy-block:first-child {
            grid-column: 1 / -1;
        }
    }

    @media (min-width: 1024px) {
        .wos-parts-table {
            width: 100%;
            padding: 0;
            display: table;
            table-layout: fixed;
        }

        .wos-parts-table thead {
            display: table-header-group;
        }

        .wos-parts-table tbody {
            display: table-row-group;
        }

        .wos-parts-table tr {
            display: table-row;
            border: 0;
            border-radius: 0;
        }

        .wos-parts-table th,
        .wos-parts-table td {
            padding: 12px 20px;
            border-bottom: 1px solid var(--ws-border);
            text-align: left;
            vertical-align: top;
        }

        .wos-parts-table th {
            background: var(--ws-card);
            color: var(--ws-text-faint);
            font-size: 10.5px;
            font-weight: 500;
            line-height: 14px;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .wos-parts-table td {
            display: table-cell;
            width: auto;
            font-size: 14px;
            line-height: 20px;
        }

        .wos-parts-table td:first-child {
            padding: 12px 20px;
            display: table-cell;
        }

        .wos-parts-table tbody tr:not(:last-child) td:last-child {
            border-bottom: 1px solid var(--ws-border);
        }

        .wos-parts-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .wos-parts-table th:nth-child(n + 3),
        .wos-parts-table td:nth-child(n + 3) {
            font-family: var(--ws-font-mono);
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }

        .wos-parts-table th:nth-child(n + 3) {
            font-family: var(--ws-font-sans);
        }

        .wos-part-cell-label {
            display: none !important;
        }

        .wos-parts-table col:nth-child(1) { width: 30%; }
        .wos-parts-table col:nth-child(2) { width: 20%; }
        .wos-parts-table col:nth-child(3) { width: 8%; }
        .wos-parts-table col:nth-child(4) { width: 14%; }
        .wos-parts-table col:nth-child(5) { width: 14%; }
        .wos-parts-table col:nth-child(6) { width: 14%; }
    }

    @media (min-width: 1180px) {
        .wos-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(340px, .85fr);
            align-items: start;
            gap: 22px;
        }
    }

    @media (max-width: 767px) {
        .wos-title {
            font-size: 26px;
        }

        .wos-actions {
            margin-left: 0;
            width: 100%;
        }

        .wos-actions .ws-secondary-action {
            flex: 1;
        }
    }
</style>
@endpush


@section('content')
    <div class="wos-page">
        @if(session('success'))
            <div class="ws-feedback-success" role="status">
                <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="ws-feedback-error" role="alert">
                @foreach($errors->all() as $error)
                    <span>{{ $error }}</span>
                @endforeach
            </div>
        @endif

        <a href="{{ route('workshop.work-orders.index') }}" class="ws-back-link">
            <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
            Όλες οι εντολές
        </a>

        <header class="wos-head">
            @if($workOrder->vehicle?->plate_number)
                <x-workshop.plate :value="$workOrder->vehicle->plate_number" size="lg" />
            @endif

            <h1 class="wos-title">Εντολή #{{ str_pad($workOrder->id, 4, '0', STR_PAD_LEFT) }}</h1>

            <x-workshop.status-badge :status="$workOrder->status" />

            <div class="wos-actions">
                <a href="{{ route('work-orders.print', $workOrder) }}" target="_blank" rel="noopener" class="ws-secondary-action">
                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 9V4h10v5M7 18H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 15h10v6H7z"/>
                    </svg>
                    Εκτύπωση
                </a>
            </div>
        </header>

        <p class="wos-sub">
            {{ $workOrder->customer?->full_name ?? 'Χωρίς πελάτη' }}
            · {{ trim(($workOrder->vehicle?->make ?? '') . ' ' . ($workOrder->vehicle?->model ?? '')) ?: 'Όχημα χωρίς στοιχεία' }}
            @if($workOrder->vehicle?->year)
                · {{ $workOrder->vehicle->year }}
            @endif
            @if(filled($workOrder->current_mileage))
                · {{ number_format($workOrder->current_mileage, 0, ',', '.') }} χλμ
            @endif
            · Άνοιγμα {{ $workOrder->created_at?->translatedFormat('d/m/Y H:i') ?? '—' }}
        </p>

        <div class="wos-layout">
            <div class="wos-stack">
                <section class="ws-panel wos-panel wos-overview" aria-labelledby="wos-overview-title">
                    <div class="ws-panel-head">
                        <h2 id="wos-overview-title" class="ws-panel-title">Εργασία</h2>
                    </div>

                    <div class="wos-copy-list">
                        <div class="ws-sunken-card wos-copy-block">
                            <span class="ws-field-label">Πρόβλημα</span>
                            <p class="wos-copy">{{ $workOrder->problem_description ?: 'Δεν έχει καταχωρηθεί περιγραφή προβλήματος.' }}</p>
                        </div>
                        <div class="ws-sunken-card wos-copy-block">
                            <span class="ws-field-label">Διάγνωση</span>
                            <p class="wos-copy @if(!$workOrder->diagnosis) wos-copy--empty @endif">{{ $workOrder->diagnosis ?: 'Δεν έχει καταχωρηθεί διάγνωση.' }}</p>
                        </div>
                        <div class="ws-sunken-card wos-copy-block">
                            <span class="ws-field-label">Εργασίες που έγιναν</span>
                            <p class="wos-copy @if(!$workOrder->work_performed) wos-copy--empty @endif">{{ $workOrder->work_performed ?: 'Δεν έχουν καταχωρηθεί εργασίες.' }}</p>
                        </div>
                    </div>
                </section>

                <section class="ws-panel wos-panel wos-parts" aria-labelledby="wos-parts-title">
                    <div class="ws-panel-head">
                        <h2 id="wos-parts-title" class="ws-panel-title">Ανταλλακτικά</h2>
                        <span class="ws-panel-meta">
                            {{ $workOrder->workOrderParts->count() }} {{ $workOrder->workOrderParts->count() === 1 ? 'γραμμή' : 'γραμμές' }}
                        </span>
                    </div>

                    @if($workOrder->workOrderParts->isEmpty())
                        <div class="wos-parts-empty">Δεν έχουν καταχωρηθεί ανταλλακτικά.</div>
                    @else
                        <table class="wos-parts-table">
                            <colgroup>
                                <col><col><col><col><col><col>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th scope="col">Ανταλλακτικό</th>
                                    <th scope="col">Πηγή</th>
                                    <th scope="col">Ποσ.</th>
                                    <th scope="col">Κόστος</th>
                                    <th scope="col">Τιμή</th>
                                    <th scope="col">Σύνολο</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workOrder->workOrderParts as $wop)
                                    @php
                                        $lineTotal = $wop->line_total ?? ($wop->quantity * $wop->unit_price);
                                        $quantity = rtrim(rtrim(number_format((float) $wop->quantity, 1, ',', '.'), '0'), ',');
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="wos-part-cell-label">Ανταλλακτικό</span>
                                            <span class="wos-part-cell-content">
                                                <span class="wos-part-name">{{ $wop->displayName() }}</span>
                                                @if($wop->description && $wop->description !== $wop->displayName())
                                                    <span class="wos-part-description">Περιγραφή: {{ $wop->description }}</span>
                                                @endif
                                                @if($wop->part?->code)
                                                    <span class="wos-part-description">Κωδικός: {{ $wop->part->code }}</span>
                                                @endif
                                                <span class="wos-part-note">Σημείωση: {{ $wop->note ?: '—' }}</span>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="wos-part-cell-label">Πηγή</span>
                                            <span class="wos-part-cell-content">
                                                <span class="wos-part-source wos-part-source--{{ $wop->source }}">
                                                    <x-workshop.part-source-label :source="$wop->source" />
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="wos-part-cell-label">Ποσότητα</span>
                                            <span class="wos-part-cell-content">{{ $quantity }}</span>
                                        </td>
                                        <td>
                                            <span class="wos-part-cell-label">Κόστος</span>
                                            <span class="wos-part-cell-content">{{ is_null($wop->unit_cost) ? '—' : number_format($wop->unit_cost, 2, ',', '.') . ' €' }}</span>
                                        </td>
                                        <td>
                                            <span class="wos-part-cell-label">Τιμή</span>
                                            <span class="wos-part-cell-content">{{ number_format($wop->unit_price ?? 0, 2, ',', '.') }} €</span>
                                        </td>
                                        <td>
                                            <span class="wos-part-cell-label">Σύνολο</span>
                                            <span class="wos-part-cell-content">{{ number_format($lineTotal, 2, ',', '.') }} €</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <div class="wos-totals">
                        <div class="wos-tot">
                            <span>Εργασία</span>
                            <span class="wos-tot-value">{{ number_format($workOrder->labor_cost ?? 0, 2, ',', '.') }} €</span>
                        </div>
                        <div class="wos-tot">
                            <span>Ανταλλακτικά</span>
                            <span class="wos-tot-value">{{ number_format($workOrder->parts_cost ?? 0, 2, ',', '.') }} €</span>
                        </div>
                        <div class="wos-tot wos-tot--grand">
                            <span>Σύνολο</span>
                            <span class="wos-tot-value">{{ number_format($workOrder->total_cost ?? 0, 2, ',', '.') }} €</span>
                        </div>
                    </div>
                </section>
            </div>

            <div class="wos-stack">
                <section class="ws-panel wos-panel wos-customer" aria-labelledby="wos-customer-title">
                    <div class="ws-panel-head">
                        <h2 id="wos-customer-title" class="ws-panel-title">Πελάτης &amp; όχημα</h2>
                    </div>
                    <dl class="ws-info-list">
                        <div class="ws-info-row">
                            <dt>Πελάτης</dt>
                            <dd>{{ $workOrder->customer?->full_name ?? '—' }}</dd>
                        </div>
                        <div class="ws-info-row">
                            <dt>Τηλέφωνο</dt>
                            <dd>
                                @if($workOrder->customer?->phone)
                                    <a href="tel:{{ $workOrder->customer->phone }}" class="wos-info-link">{{ $workOrder->customer->phone }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="ws-info-row">
                            <dt>Όχημα</dt>
                            <dd>{{ trim(($workOrder->vehicle?->make ?? '') . ' ' . ($workOrder->vehicle?->model ?? '')) ?: '—' }}</dd>
                        </div>
                        <div class="ws-info-row">
                            <dt>Πινακίδα</dt>
                            <dd>
                                @if($workOrder->vehicle?->plate_number)
                                    <x-workshop.plate :value="$workOrder->vehicle->plate_number" size="sm" />
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="ws-info-row">
                            <dt>ΚΤΕΟ</dt>
                            <dd>{{ $workOrder->vehicle?->kteo_expires_at?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="ws-panel wos-panel wos-status" aria-labelledby="wos-status-title">
                    <div class="ws-panel-head">
                        <h2 id="wos-status-title" class="ws-panel-title">Αλλαγή κατάστασης</h2>
                    </div>
                    <div class="wos-status-body">
                        @foreach($statusOptions as $status)
                            @if($status === $workOrder->status)
                                <span
                                    class="wos-status-btn wos-status-btn--{{ $status->value }} is-current"
                                    aria-current="true"
                                    aria-label="Τρέχουσα κατάσταση: {{ $status->label() }}"
                                >
                                    {{ $status->label() }}
                                </span>
                            @elseif($status === \App\Enums\WorkOrderStatus::Completed)
                                <form method="POST" action="{{ route('workshop.work-orders.status', $workOrder) }}" class="wos-status-form wos-status-form--completion">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status->value }}">

                                    <label class="wos-closure-label" for="wos-closure-document-{{ $workOrder->id }}">Παραστατικό</label>
                                    <select
                                        id="wos-closure-document-{{ $workOrder->id }}"
                                        name="closure_document"
                                        class="wos-closure-select"
                                        onchange="document.getElementById('wos-non-issue-reason-{{ $workOrder->id }}').hidden = (this.value !== 'none')"
                                    >
                                        @foreach(\App\Enums\ClosureDocument::cases() as $option)
                                            <option value="{{ $option->value }}" @selected($option === \App\Enums\ClosureDocument::RetailReceipt)>{{ $option->label() }}</option>
                                        @endforeach
                                    </select>

                                    <div id="wos-non-issue-reason-{{ $workOrder->id }}" hidden>
                                        <label class="wos-closure-label" for="wos-non-issue-reason-select-{{ $workOrder->id }}">Αιτιολογία μη έκδοσης</label>
                                        <select
                                            id="wos-non-issue-reason-select-{{ $workOrder->id }}"
                                            name="non_issue_reason"
                                            class="wos-closure-select"
                                        >
                                            @foreach(\App\Enums\NonIssueReason::cases() as $option)
                                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <button type="submit" class="wos-status-btn wos-status-btn--{{ $status->value }}">
                                        {{ $status->label() }}
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('workshop.work-orders.status', $workOrder) }}" class="wos-status-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status->value }}">
                                    <button type="submit" class="wos-status-btn wos-status-btn--{{ $status->value }}">
                                        {{ $status->label() }}
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </section>

                <section class="ws-panel wos-panel wos-service" aria-labelledby="wos-service-title">
                    <div class="ws-panel-head">
                        <h2 id="wos-service-title" class="ws-panel-title">Παρακολούθηση service</h2>
                    </div>
                    <dl class="ws-info-list">
                        <div class="ws-info-row">
                            <dt>Τρέχοντα χλμ.</dt>
                            <dd>{{ filled($workOrder->current_mileage) ? number_format($workOrder->current_mileage, 0, ',', '.') . ' km' : '—' }}</dd>
                        </div>
                        <div class="ws-info-row">
                            <dt>Επόμενο service</dt>
                            <dd>{{ $workOrder->next_service_date?->translatedFormat('d F Y') ?? '—' }}</dd>
                        </div>
                        <div class="ws-info-row">
                            <dt>Service στα χλμ.</dt>
                            <dd>{{ filled($workOrder->next_service_mileage) ? number_format($workOrder->next_service_mileage, 0, ',', '.') . ' km' : '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>
@endsection
