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

    .wos-layout {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .wos-overview { order: 1; }
    .wos-customer { order: 2; }
    .wos-status { order: 3; }
    .wos-service { order: 4; }
    .wos-parts { order: 5; }
    .wos-costs { order: 6; }

    .wos-summary-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        border-bottom: 1px solid var(--ws-border);
    }

    .wos-summary-item {
        min-width: 0;
        padding: 15px 18px;
        border-bottom: 1px solid var(--ws-border);
    }

    .wos-summary-item:last-child {
        border-bottom: 0;
    }

    .wos-value {
        display: block;
        color: var(--ws-text);
        font-family: var(--ws-font-serif);
        font-size: 14px;
        font-weight: 700;
        line-height: 20px;
        overflow-wrap: anywhere;
    }

    .wos-order-number {
        color: var(--ws-primary);
        font-family: var(--ws-font-sans);
    }

    .wos-copy-list {
        padding: 18px;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 16px;
    }

    .wos-copy {
        margin: 0;
        color: var(--ws-text);
        font-family: var(--ws-font-serif);
        font-size: 14px;
        line-height: 1.65;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .wos-copy--empty {
        color: var(--ws-text-muted);
    }

    .wos-info-link {
        color: var(--ws-primary);
    }

    .wos-info-link:hover {
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .wos-panel .ws-info-row .ws-plate {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wos-status-body {
        padding: 16px 18px 18px;
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
        border-radius: 10px;
        background: var(--ws-card);
        color: var(--ws-text-muted);
        font-family: var(--ws-font-sans);
        font-size: 13px;
        font-weight: 600;
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
        opacity: .55;
    }

    .wos-status-btn:hover:not(.is-current) {
        border-color: var(--ws-border-hover);
        background: var(--ws-sunken);
        color: var(--ws-text);
    }

    .wos-status-btn.is-current {
        border-color: var(--ws-primary);
        background: var(--ws-status-progress-bg);
        color: var(--ws-status-progress-fg);
        cursor: default;
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
        border-radius: 12px;
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
        font-size: 12px;
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
        color: var(--ws-text-muted);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
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
        font-family: var(--ws-font-serif);
        font-size: 14px;
        font-weight: 700;
        line-height: 20px;
    }

    .wos-part-description,
    .wos-part-note {
        margin-top: 3px;
        color: var(--ws-text-muted);
        font-size: 11px;
        line-height: 16px;
    }

    .wos-part-source {
        display: inline-flex;
        align-items: center;
        border-radius: 8px;
        padding: 3px 8px;
        background: var(--ws-sunken);
        color: var(--ws-text-muted);
        font-size: 10px;
        font-weight: 700;
        line-height: 16px;
        text-align: left;
    }

    .wos-part-source--from_stock {
        background: var(--ws-status-progress-bg);
        color: var(--ws-status-progress-fg);
    }

    .wos-part-source--customer_supplied {
        background: var(--ws-status-ready-bg);
        color: var(--ws-status-ready-fg);
    }

    .wos-part-source--purchased_for_job {
        background: var(--ws-status-awaiting-bg);
        color: var(--ws-status-awaiting-fg);
    }

    .wos-parts-empty {
        min-height: 120px;
        padding: 24px 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ws-text-muted);
        font-family: var(--ws-font-sans);
        font-size: 13px;
        text-align: center;
    }

    .wos-cost-list {
        padding: 2px 18px;
    }

    .wos-cost-row {
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid var(--ws-border);
        font-size: 14px;
    }

    .wos-cost-row:last-child {
        border-bottom: 0;
    }

    .wos-cost-label {
        color: var(--ws-text-muted);
    }

    .wos-cost-value {
        font-family: var(--ws-font-sans);
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .wos-cost-row--total {
        min-height: 58px;
    }

    .wos-cost-row--total .wos-cost-label {
        color: var(--ws-text);
        font-weight: 700;
    }

    .wos-cost-row--total .wos-cost-value {
        color: var(--ws-primary);
        font-size: 18px;
    }

    @media (min-width: 600px) {
        .wos-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .wos-summary-item {
            border-right: 1px solid var(--ws-border);
            border-bottom: 0;
        }

        .wos-summary-item:last-child {
            border-right: 0;
        }

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
            padding: 12px;
            border-bottom: 1px solid var(--ws-border);
            text-align: left;
            vertical-align: top;
        }

        .wos-parts-table th {
            background: var(--ws-page);
            color: var(--ws-text-muted);
            font-size: 10px;
            font-weight: 700;
            line-height: 14px;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .wos-parts-table td {
            display: table-cell;
            width: auto;
            font-size: 12px;
            line-height: 18px;
        }

        .wos-parts-table td:first-child {
            padding: 12px;
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
            text-align: right;
        }

        .wos-part-cell-label {
            display: none !important;
        }

        .wos-parts-table col:nth-child(1) { width: 28%; }
        .wos-parts-table col:nth-child(2) { width: 22%; }
        .wos-parts-table col:nth-child(3) { width: 9%; }
        .wos-parts-table col:nth-child(4) { width: 13%; }
        .wos-parts-table col:nth-child(5) { width: 13%; }
        .wos-parts-table col:nth-child(6) { width: 15%; }
    }

    @media (min-width: 1280px) {
        .wos-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 336px;
            grid-template-areas:
                "overview customer"
                "parts status"
                "parts service"
                "parts costs";
            align-items: start;
            gap: 18px;
        }

        .wos-overview { grid-area: overview; }
        .wos-customer { grid-area: customer; }
        .wos-status { grid-area: status; }
        .wos-service { grid-area: service; }
        .wos-parts { grid-area: parts; }
        .wos-costs { grid-area: costs; }
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

        <a href="{{ route('workshop.work-orders.index') }}" class="ws-back-link">
            <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
            Όλες οι εντολές
        </a>

        <header class="ws-page-hero">
            <div class="ws-page-hero-copy">
                <p class="ws-page-eyebrow">Εντολή #{{ str_pad($workOrder->id, 4, '0', STR_PAD_LEFT) }}</p>
                <h1 class="ws-page-display-title">{{ $workOrder->customer?->full_name ?? 'Χωρίς πελάτη' }}</h1>
                <p class="ws-page-subtitle">
                    {{ trim(($workOrder->vehicle?->make ?? '') . ' ' . ($workOrder->vehicle?->model ?? '')) ?: 'Όχημα χωρίς στοιχεία' }}
                    @if($workOrder->vehicle?->plate_number)
                        · {{ $workOrder->vehicle->plate_number }}
                    @endif
                </p>
            </div>

            <div class="ws-page-actions">
                <x-workshop.status-badge :status="$workOrder->status" />
                <a href="{{ route('work-orders.print', $workOrder) }}" target="_blank" rel="noopener" class="ws-secondary-action">
                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                    </svg>
                    Εκτύπωση
                </a>
            </div>
        </header>

        <div class="wos-layout">
            <section class="ws-panel wos-panel wos-overview" aria-labelledby="wos-overview-title">
                <div class="ws-panel-head">
                    <h2 id="wos-overview-title" class="ws-panel-title">Στοιχεία εντολής</h2>
                </div>

                <div class="wos-summary-grid">
                    <div class="wos-summary-item">
                        <span class="ws-field-label">Αριθμός</span>
                        <span class="wos-value wos-order-number">#{{ str_pad($workOrder->id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="wos-summary-item">
                        <span class="ws-field-label">Κατάσταση</span>
                        <span class="wos-value"><x-workshop.status-badge :status="$workOrder->status" /></span>
                    </div>
                    <div class="wos-summary-item">
                        <span class="ws-field-label">Άνοιγμα</span>
                        <time class="wos-value" @if($workOrder->created_at) datetime="{{ $workOrder->created_at->toIso8601String() }}" @endif>
                            {{ $workOrder->created_at?->translatedFormat('d F Y, H:i') ?? '—' }}
                        </time>
                    </div>
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
                                <x-workshop.plate :value="$workOrder->vehicle->plate_number" />
                            @else
                                —
                            @endif
                        </dd>
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
            </section>

            <section class="ws-panel wos-panel wos-costs" aria-labelledby="wos-costs-title">
                <div class="ws-panel-head">
                    <h2 id="wos-costs-title" class="ws-panel-title">Κόστος</h2>
                </div>
                <div class="wos-cost-list">
                    <div class="wos-cost-row">
                        <span class="wos-cost-label">Εργασία</span>
                        <span class="wos-cost-value">{{ number_format($workOrder->labor_cost ?? 0, 2, ',', '.') }} €</span>
                    </div>
                    <div class="wos-cost-row">
                        <span class="wos-cost-label">Ανταλλακτικά</span>
                        <span class="wos-cost-value">{{ number_format($workOrder->parts_cost ?? 0, 2, ',', '.') }} €</span>
                    </div>
                    <div class="wos-cost-row wos-cost-row--total">
                        <span class="wos-cost-label">Σύνολο</span>
                        <span class="wos-cost-value">{{ number_format($workOrder->total_cost ?? 0, 2, ',', '.') }} €</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
