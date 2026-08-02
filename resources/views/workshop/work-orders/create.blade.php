@extends('layouts.workshop')

@section('title', 'Νέα εντολή εργασίας — Συνεργείο')
@section('header-title', 'Νέα εντολή')

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
        margin: 0 0 1.375rem;
        color: var(--ws-text);
        font-size: 1.75rem;
        font-weight: 500;
        line-height: 2rem;
    }

    /* ── Form card ───────────────────────────────────────────── */
    .woc-card {
        margin-bottom: 1rem;
        border: 1px solid var(--ws-border);
        border-radius: 12px;
        background: var(--ws-card);
        overflow: visible;
    }
    .woc-form-section {
        padding: 1.25rem;
    }
    .woc-form-section + .woc-form-section {
        border-top: 1px solid var(--ws-border);
    }
    .woc-form-section-title {
        margin: 0 0 0.625rem;
        color: var(--ws-text-muted);
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1rem;
    }
    .woc-form-section-body {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .woc-card-body {
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
        font-weight: 500;
        color: var(--ws-text);
    }
    .woc-label .woc-req { color: var(--ws-text-faint); margin-left: 2px; }

    .woc-input,
    .woc-select,
    .woc-textarea {
        width: 100%;
        min-height: 36px;
        padding: 0.45rem 0.75rem;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        background: var(--ws-card);
        color: var(--ws-text);
        font-size: 0.875rem;
        transition: border-color 0.15s, box-shadow 0.15s;
        -webkit-appearance: none;
        font-family: inherit;
    }
    .woc-input,
    .woc-select {
        height: 36px;
    }
    .woc-select {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%238b949e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.5rem;
    }
    .woc-textarea {
        min-height: 0;
        max-height: calc(8 * 1.25rem + 1rem);
        resize: none;
        overflow-y: hidden;
        line-height: 1.25rem;
    }
    .woc-input::placeholder,
    .woc-textarea::placeholder { color: var(--ws-text-faint); }
    .woc-input:focus,
    .woc-select:focus,
    .woc-textarea:focus {
        border-color: var(--ws-primary);
        outline: 2px solid var(--ws-primary);
        outline-offset: 2px;
        box-shadow: none;
    }
    .woc-input.is-invalid,
    .woc-select.is-invalid,
    .woc-textarea.is-invalid {
        border-color: var(--danger);
        box-shadow: none;
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
        color: var(--ws-primary-fg);
        font-size: 0.625rem;
        font-weight: 500;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .woc-hint {
        font-size: 0.75rem;
        color: var(--text-faint);
        line-height: 1.4;
    }

    /* ── Currency wrapper ────────────────────────────────────── */
    .woc-eur {
        position: relative;
    }
    .woc-eur .woc-eur-sym {
        position: absolute;
        right: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.875rem;
        color: var(--text-faint);
        pointer-events: none;
        font-weight: 500;
    }
    .woc-eur .woc-input {
        padding-right: 2rem;
        text-align: right;
        font-variant-numeric: tabular-nums;
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

    /* ── Source radio pills ──────────────────────────────────── */
    .woc-source-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .woc-source-pill {
        display: none; /* hide the real radio */
    }
    .woc-source-label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        font-weight: 500;
        padding: 0.45rem 0.875rem;
        border: 1px solid var(--border);
        border-radius: 999px;
        cursor: pointer;
        color: var(--text-muted);
        transition: color 0.12s, background 0.12s, border-color 0.12s;
        user-select: none;
    }
    .woc-source-label:hover {
        color: var(--text);
        border-color: var(--text-faint);
    }
    .woc-source-pill:checked + .woc-source-label {
        background: var(--ws-sunken);
        border-color: var(--ws-border-hover);
        color: var(--ws-text);
    }

    /* ── Repeater row ────────────────────────────────────────── */
    .woc-parts-block {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        border-top: 1px solid var(--ws-border);
    }
    .woc-part-row {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid var(--border-soft, var(--border));
    }
    .woc-part-row:last-of-type {
        padding-bottom: 0;
        border-bottom: none;
    }
    .woc-part-row-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .woc-part-row-num {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--ws-text-muted);
    }
    .woc-remove-btn {
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--ws-text-muted);
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px;
        padding: 0.3rem 0.6rem;
        cursor: pointer;
        transition: color 0.15s, background 0.15s, border-color 0.15s;
    }
    .woc-remove-btn:hover {
        color: var(--danger);
        background: var(--danger-dim);
        border-color: var(--ws-border);
    }
    .woc-remove-btn svg { width: 12px; height: 12px; stroke-width: 2.5; }
    .woc-add-btn {
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        align-self: flex-start;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--ws-text);
        background: transparent;
        border: 1px solid var(--ws-border);
        border-radius: 8px;
        padding: 0.5rem 0.875rem;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
    }
    .woc-add-btn:hover {
        background: var(--ws-sunken);
        border-color: var(--ws-border-hover);
    }
    .woc-add-btn svg { width: 14px; height: 14px; stroke-width: 2.5; }

    /* ── Conditional fields grid ─────────────────────────────── */
    .woc-part-fields {
        display: none; /* hidden until source chosen */
        flex-direction: column;
        gap: 0.875rem;
        padding-top: 0.25rem;
        border-top: 1px solid var(--border);
        margin-top: 0.25rem;
    }
    .woc-part-fields.is-visible { display: flex; }

    .woc-part-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .woc-part-grid .span2 { grid-column: span 2; }

    /* ── Subtotal row ────────────────────────────────────────── */
    .woc-line-total {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        font-size: 0.8125rem;
        color: var(--text-faint);
        padding-top: 0.375rem;
    }
    .woc-line-total-val {
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        color: var(--text);
        min-width: 90px;
        text-align: right;
        font-size: 0.9375rem;
    }
    .woc-line-total-input {
        background: var(--ws-sunken);
        color: var(--ws-text);
        font-weight: 500;
        cursor: default;
    }

    /* ── Summary ─────────────────────────────────────────────── */
    .woc-summary {
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        overflow: hidden;
    }
    .woc-sum-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.55rem 1rem;
        font-size: 0.875rem;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border);
    }
    .woc-sum-row:last-child { border-bottom: none; }
    .woc-sum-row--grand {
        font-weight: 500;
        font-size: 0.9375rem;
        color: var(--text);
        background: var(--surface);
    }
    .woc-sum-val { font-weight: 500; font-variant-numeric: tabular-nums; }
    .woc-sum-row--grand .woc-sum-val { color: var(--ws-text); }

    /* ── Sticky action bar ───────────────────────────────────── */
    .woc-action-bar {
        position: sticky;
        bottom: 0;
        z-index: 10;
        min-height: 64px;
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-top: 1px solid var(--ws-border);
        border-radius: 0 0 12px 12px;
        background: var(--ws-sunken);
    }
    .woc-action-summary {
        color: var(--ws-text-muted);
        font-size: 0.8125rem;
    }
    .woc-action-summary strong {
        margin-left: 0.375rem;
        color: var(--ws-text);
        font-size: 0.9375rem;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
    }
    .woc-action-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
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
        font-weight: 500;
        color: var(--ws-primary-fg);
        background: var(--ws-primary);
        border: none;
        border-radius: 8px;
        padding: 0.55rem 1.375rem;
        cursor: pointer;
        transition: opacity 0.15s;
    }
    .woc-submit-btn:hover { opacity: 0.92; }
    .woc-submit-btn svg { width: 15px; height: 15px; stroke-width: 2.5; }

    /* ── Desktop ─────────────────────────────────────────────── */
    @media (min-width: 768px) {
        .woc-card-body--grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        .woc-card-body--grid .span2 { grid-column: span 2; }
        .woc-part-grid {
            grid-template-columns: 2fr 1fr 1fr 1fr;
        }
        .woc-part-grid .span2 { grid-column: span 2; }
        .woc-part-grid .span4 { grid-column: span 4; }
    }
</style>
@endpush

@section('content')

<div class="woc-toolbar">
    <a href="{{ route('workshop.work-orders.index') }}" class="woc-back-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
        </svg>
        Εργασίες
    </a>
</div>

<h1 class="woc-page-title">Νέα εντολή εργασίας</h1>

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

@php
    $oldParts = old('parts');
    $blankPartRow = [
        'source' => '', 'part_id' => '', 'description' => '',
        'quantity' => '1', 'unit_cost' => '', 'unit_price' => '', 'note' => '',
    ];
    $initialParts = is_array($oldParts) && count($oldParts) > 0
        ? array_map(fn ($row) => array_merge($blankPartRow, is_array($row) ? $row : []), array_values($oldParts))
        : [];
    $partsCatalog = $parts->mapWithKeys(fn ($p) => [$p->id => [
        'purchase_price' => $p->purchase_price,
        'sale_price'     => $p->sale_price,
    ]]);
    // Single-quoted HTML attribute + HEX flags: @json() output always
    // contains double quotes (JSON string delimiters), which would
    // otherwise prematurely terminate a double-quoted x-data="...".
    $jsonFlags = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG;
@endphp

<form method="POST" action="{{ route('workshop.work-orders.store') }}" novalidate
    x-data='workOrderForm(@json($initialParts, $jsonFlags), @json($partsCatalog, $jsonFlags), {{ (float) old('labor_cost', 0) }}, @json($errors->messages(), $jsonFlags))'>
    @csrf

    <div class="woc-card" data-work-order-form-card>
        <x-workshop.form.section title="Πελάτης και όχημα">
        <div class="woc-card-body woc-card-body--grid">

            <div class="woc-field">
                <label for="customer_id" class="woc-label">Πελάτης <span class="woc-req">*</span></label>
                <select id="customer_id" name="customer_id"
                    class="woc-select {{ $errors->has('customer_id') ? 'is-invalid' : '' }}">
                    <option value="">— Επιλογή πελάτη —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', request()->query('customer_id')) == $customer->id ? 'selected' : '' }}>
                            {{ $customer->full_name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

            <div class="woc-field">
                <label for="vehicle_id" class="woc-label">Όχημα <span class="woc-req">*</span></label>
                <select id="vehicle_id" name="vehicle_id" disabled
                    class="woc-select {{ $errors->has('vehicle_id') ? 'is-invalid' : '' }}">
                    <option value="">Πρώτα επιλέξτε πελάτη</option>
                </select>
                @error('vehicle_id') <span class="woc-error">{{ $message }}</span> @enderror
                <span class="woc-hint">Πινακίδα — Μάρκα Μοντέλο</span>
            </div>

        </div>
        </x-workshop.form.section>
        <x-workshop.form.section title="Τι δηλώνει ο πελάτης">
        <div class="woc-card-body woc-card-body--grid">
            <div class="woc-field span2">
                <label for="problem_description" class="woc-label">
                    Πρόβλημα ή εργασία <span class="woc-req">*</span>
                </label>
                <textarea id="problem_description" name="problem_description" rows="2" data-autogrow
                    class="woc-textarea {{ $errors->has('problem_description') ? 'is-invalid' : '' }}"
                    placeholder="Τριγμός από εμπρός δεξιά κατά την οδήγηση">{{ old('problem_description') }}</textarea>
                @error('problem_description') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

            <div class="woc-field">
                <label for="diagnosis" class="woc-label">Διάγνωση συνεργείου</label>
                <textarea id="diagnosis" name="diagnosis" rows="2" data-autogrow
                    class="woc-textarea {{ $errors->has('diagnosis') ? 'is-invalid' : '' }}"
                    placeholder="Καταγράψτε τα ευρήματα του ελέγχου">{{ old('diagnosis') }}</textarea>
                @error('diagnosis') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

            <div class="woc-field">
                <label for="work_performed" class="woc-label">Εργασίες που πραγματοποιήθηκαν</label>
                <textarea id="work_performed" name="work_performed" rows="2" data-autogrow
                    class="woc-textarea {{ $errors->has('work_performed') ? 'is-invalid' : '' }}"
                    placeholder="Καταγράψτε τις εργασίες που ολοκληρώθηκαν">{{ old('work_performed') }}</textarea>
                @error('work_performed') <span class="woc-error">{{ $message }}</span> @enderror
            </div>
        </div>
        </x-workshop.form.section>

        <x-workshop.form.section title="Κόστος και ανταλλακτικά">
        <div class="woc-card-body">
            <div class="woc-field" style="max-width:220px;">
                <label for="labor_cost" class="woc-label">Αμοιβή εργασίας</label>
                <div class="woc-eur">
                    <input type="number" id="labor_cost" name="labor_cost"
                        class="woc-input {{ $errors->has('labor_cost') ? 'is-invalid' : '' }}"
                        x-model.number="laborCost"
                        step="0.01" placeholder="0,00" lang="el">
                    <span class="woc-eur-sym">€</span>
                </div>
                @error('labor_cost') <span class="woc-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="woc-parts-block">

            <template x-for="(row, index) in parts" :key="row._key">
                <div class="woc-part-row">

                    <div class="woc-part-row-head">
                        <span class="woc-part-row-num" x-text="'Γραμμή ' + (index + 1)"></span>
                        <button type="button" class="woc-remove-btn" @click="removeRow(index)">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397M4.772 5.79c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                            <span x-text="parts.length === 1 ? 'Καθαρισμός' : 'Αφαίρεση'"></span>
                        </button>
                    </div>

                    {{-- Source radio pills --}}
                    <div class="woc-field">
                        <label class="woc-label">Προέλευση</label>
                        <div class="woc-source-wrap">

                            <input type="radio" class="woc-source-pill" value="from_stock"
                                   :name="'parts['+index+'][source]'" :id="'src-stock-'+row._key"
                                   x-model="row.source" @change="onSourceChange(row)">
                            <label :for="'src-stock-'+row._key" class="woc-source-label">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                </svg>
                                <x-workshop.part-source-label source="from_stock" />
                            </label>

                            <input type="radio" class="woc-source-pill" value="customer_supplied"
                                   :name="'parts['+index+'][source]'" :id="'src-cust-'+row._key"
                                   x-model="row.source" @change="onSourceChange(row)">
                            <label :for="'src-cust-'+row._key" class="woc-source-label">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                </svg>
                                <x-workshop.part-source-label source="customer_supplied" />
                            </label>

                            <input type="radio" class="woc-source-pill" value="purchased_for_job"
                                   :name="'parts['+index+'][source]'" :id="'src-job-'+row._key"
                                   x-model="row.source" @change="onSourceChange(row)">
                            <label :for="'src-job-'+row._key" class="woc-source-label">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                                </svg>
                                <x-workshop.part-source-label source="purchased_for_job" />
                            </label>

                        </div>
                        <span class="woc-hint">Αφήστε χωρίς επιλογή αν δεν υπάρχει ανταλλακτικό σε αυτή τη γραμμή.</span>
                        <template x-if="errorFor(index, 'source')">
                            <span class="woc-error" x-text="errorFor(index, 'source')"></span>
                        </template>
                    </div>

                    {{-- ── Conditional fields ────────────────────────── --}}
                    <div class="woc-part-fields" :class="{ 'is-visible': row.source }">

                        {{-- Part dropdown — only for from_stock --}}
                        <div x-show="row.source === 'from_stock'">
                            <div class="woc-field">
                                <label :for="'part-id-'+row._key" class="woc-label">
                                    Ανταλλακτικό <span class="woc-req">*</span>
                                </label>
                                <select :id="'part-id-'+row._key" :name="'parts['+index+'][part_id]'"
                                    class="woc-select" x-model="row.part_id" @change="onPartSelect(row)">
                                    <option value="">— Επιλογή από απόθεμα —</option>
                                    @foreach($parts as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->name }}
                                            @if($p->quantity !== null)
                                                (αποθ: {{ rtrim(rtrim(number_format((float) $p->quantity, 1, ',', '.'), '0'), ',') }})
                                            @endif
                                            @if($p->sale_price) — {{ number_format($p->sale_price, 2, ',', '.') }} € @endif
                                        </option>
                                    @endforeach
                                </select>
                                <template x-if="errorFor(index, 'part_id')">
                                    <span class="woc-error" x-text="errorFor(index, 'part_id')"></span>
                                </template>
                            </div>
                        </div>

                        {{-- Description — for non-stock --}}
                        <div x-show="row.source && row.source !== 'from_stock'">
                            <div class="woc-field">
                                <label :for="'desc-'+row._key" class="woc-label">
                                    Περιγραφή <span class="woc-req">*</span>
                                </label>
                                <input type="text" :id="'desc-'+row._key" :name="'parts['+index+'][description]'"
                                    class="woc-input" x-model="row.description"
                                    placeholder="π.χ. Λάδι κινητήρα 5W-40…">
                                <template x-if="errorFor(index, 'description')">
                                    <span class="woc-error" x-text="errorFor(index, 'description')"></span>
                                </template>
                            </div>
                        </div>

                        {{-- Qty / unit_cost / unit_price grid --}}
                        <div class="woc-part-grid">

                            <div class="woc-field">
                                <label :for="'qty-'+row._key" class="woc-label">Ποσότητα <span class="woc-req">*</span></label>
                                <input type="number" :id="'qty-'+row._key" :name="'parts['+index+'][quantity]'"
                                    class="woc-input" x-model="row.quantity"
                                    min="0.5" step="0.5" placeholder="1" lang="el">
                                <template x-if="errorFor(index, 'quantity')">
                                    <span class="woc-error" x-text="errorFor(index, 'quantity')"></span>
                                </template>
                            </div>

                            {{-- unit_cost: hidden for customer_supplied --}}
                            <div class="woc-field" x-show="row.source !== 'customer_supplied'">
                                <label :for="'cost-'+row._key" class="woc-label">Κόστος / τεμ.</label>
                                <div class="woc-eur">
                                    <input type="number" :id="'cost-'+row._key" :name="'parts['+index+'][unit_cost]'"
                                        class="woc-input" x-model="row.unit_cost"
                                        step="0.01" placeholder="0,00" lang="el">
                                    <span class="woc-eur-sym">€</span>
                                </div>
                                <template x-if="errorFor(index, 'unit_cost')">
                                    <span class="woc-error" x-text="errorFor(index, 'unit_cost')"></span>
                                </template>
                            </div>

                            <div class="woc-field">
                                <label :for="'price-'+row._key" class="woc-label">Τιμή / τεμ.</label>
                                <div class="woc-eur">
                                    <input type="number" :id="'price-'+row._key" :name="'parts['+index+'][unit_price]'"
                                        class="woc-input" x-model="row.unit_price"
                                        step="0.01" placeholder="0,00" lang="el">
                                    <span class="woc-eur-sym">€</span>
                                </div>
                                <template x-if="errorFor(index, 'unit_price')">
                                    <span class="woc-error" x-text="errorFor(index, 'unit_price')"></span>
                                </template>
                            </div>

                            {{-- Subtotal (read-only display, client-side only — server recomputes) --}}
                            <div class="woc-field">
                                <label class="woc-label">Σύνολο γραμμής</label>
                                <div class="woc-eur">
                                    <input type="text" class="woc-input woc-line-total-input" readonly tabindex="-1"
                                        :value="fmt(lineTotal(row))">
                                    <span class="woc-eur-sym">€</span>
                                </div>
                            </div>

                        </div>

                        <div class="woc-field">
                            <label :for="'note-'+row._key" class="woc-label">Σημείωση</label>
                            <input type="text" :id="'note-'+row._key" :name="'parts['+index+'][note]'"
                                class="woc-input" x-model="row.note" placeholder="Προαιρετικό…">
                            <template x-if="errorFor(index, 'note')">
                                <span class="woc-error" x-text="errorFor(index, 'note')"></span>
                            </template>
                        </div>

                    </div>{{-- /part-fields --}}

                </div>{{-- /woc-part-row --}}
            </template>

            <button type="button" class="woc-add-btn" @click="addRow()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Προσθήκη ανταλλακτικού
            </button>

        </div>
        </x-workshop.form.section>

        <x-workshop.form.section title="Στοιχεία service">
        <div class="woc-card-body woc-card-body--grid">

            <div class="woc-field">
                <label for="current_mileage" class="woc-label">Τρέχοντα χιλιόμετρα</label>
                <input type="number" id="current_mileage" name="current_mileage"
                    class="woc-input {{ $errors->has('current_mileage') ? 'is-invalid' : '' }}"
                    value="{{ old('current_mileage') }}"
                    min="0" step="1" placeholder="π.χ. 85000">
                @error('current_mileage') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

            <div class="woc-field">
                <label for="next_service_date" class="woc-label">Ημερομηνία επόμενου service</label>
                <input type="date" id="next_service_date" name="next_service_date"
                    class="woc-input {{ $errors->has('next_service_date') ? 'is-invalid' : '' }}"
                    value="{{ old('next_service_date') }}">
                @error('next_service_date') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

            <div class="woc-field">
                <label for="next_service_mileage" class="woc-label">Χιλιόμετρα επόμενου service</label>
                <input type="number" id="next_service_mileage" name="next_service_mileage"
                    class="woc-input {{ $errors->has('next_service_mileage') ? 'is-invalid' : '' }}"
                    value="{{ old('next_service_mileage') }}"
                    min="0" step="1" placeholder="π.χ. 95000">
                @error('next_service_mileage') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

        </div>
        </x-workshop.form.section>

        <x-workshop.form.section title="Σύνοψη κόστους">
            <div class="woc-card-body">
                <div class="woc-summary">
                    <div class="woc-sum-row">
                        <span>Αμοιβή εργασίας</span>
                        <span class="woc-sum-val" x-text="fmt(laborCost)">0,00 €</span>
                    </div>
                    <div class="woc-sum-row">
                        <span>Ανταλλακτικά</span>
                        <span class="woc-sum-val" x-text="fmt(partsTotal)">0,00 €</span>
                    </div>
                    <div class="woc-sum-row woc-sum-row--grand">
                        <span>Σύνολο</span>
                        <span class="woc-sum-val" x-text="fmt(grandTotal)">0,00 €</span>
                    </div>
                </div>
            </div>
        </x-workshop.form.section>

        <x-workshop.form.action-bar>
            <x-slot:summary>
                Σύνολο <strong x-text="fmt(grandTotal)">0,00 €</strong>
            </x-slot:summary>

            <a href="{{ route('workshop.work-orders.index') }}" class="woc-cancel-btn">Ακύρωση</a>
            <button type="submit" class="woc-submit-btn">
                Αποθήκευση
            </button>
        </x-workshop.form.action-bar>
    </div>

</form>
@endsection

@push('scripts')
<script>
/* ────────────────────────────────────────────────────────────
   Vehicle dropdown — scoped to the selected customer
─────────────────────────────────────────────────────────── */
const VEHICLES_BY_CUSTOMER = @json($vehiclesByCustomer);

function autoGrowWorkOrderTextarea(textarea) {
    const styles = window.getComputedStyle(textarea);
    const lineHeight = parseFloat(styles.lineHeight) || 20;
    const maxRows = 8;
    const verticalPadding = parseFloat(styles.paddingTop) + parseFloat(styles.paddingBottom);
    const maxHeight = (lineHeight * maxRows) + verticalPadding;

    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, maxHeight) + 'px';
    textarea.style.overflowY = textarea.scrollHeight > maxHeight ? 'auto' : 'hidden';
}

function populateVehicles(customerId, selectedVehicleId) {
    const sel = document.getElementById('vehicle_id');
    sel.innerHTML = '';

    if (!customerId) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Πρώτα επιλέξτε πελάτη';
        sel.appendChild(opt);
        sel.disabled = true;
        return;
    }

    const vehicles = VEHICLES_BY_CUSTOMER[customerId] || [];

    if (vehicles.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Ο πελάτης δεν έχει καταχωρημένο όχημα';
        sel.appendChild(opt);
        sel.disabled = true;
        return;
    }

    sel.disabled = false;

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '— Επιλογή οχήματος —';
    sel.appendChild(placeholder);

    vehicles.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.id;
        opt.textContent = v.label;
        sel.appendChild(opt);
    });

    if (vehicles.length === 1) {
        sel.value = String(vehicles[0].id);
    } else if (selectedVehicleId && vehicles.some(v => String(v.id) === String(selectedVehicleId))) {
        sel.value = String(selectedVehicleId);
    } else {
        sel.value = '';
    }

    fillMileageForSelectedVehicle(customerId, sel.value);
}

// Auto-fill "current mileage" from the vehicle's last known reading — only
// when the field is still empty, so it never overwrites a value the staff
// already typed (or one restored via old() after a validation error).
function fillMileageForSelectedVehicle(customerId, vehicleId) {
    const mileageInput = document.getElementById('current_mileage');
    if (!mileageInput || mileageInput.value !== '') return;

    const vehicles = VEHICLES_BY_CUSTOMER[customerId] || [];
    const vehicle = vehicles.find(v => String(v.id) === String(vehicleId));
    if (vehicle && vehicle.mileage !== null && vehicle.mileage !== undefined) {
        mileageInput.value = vehicle.mileage;
    }
}

document.getElementById('customer_id').addEventListener('change', function () {
    populateVehicles(this.value, null);
});

document.getElementById('vehicle_id').addEventListener('change', function () {
    fillMileageForSelectedVehicle(document.getElementById('customer_id').value, this.value);
});

document.addEventListener('DOMContentLoaded', function () {
    // Restore vehicle dropdown scoped to the old (or query-string) customer_id, if any
    const customerSel = document.getElementById('customer_id');
    populateVehicles(customerSel.value, @json(old('vehicle_id', request()->query('vehicle_id'))));

    document.querySelectorAll('[data-autogrow]').forEach(textarea => {
        autoGrowWorkOrderTextarea(textarea);
        textarea.addEventListener('input', () => autoGrowWorkOrderTextarea(textarea));
    });
});

/* ────────────────────────────────────────────────────────────
   Ανταλλακτικά repeater — Alpine.js component.
   Registered via Alpine.data() inside the alpine:init event, which
   Alpine fires synchronously right before it scans the DOM — this
   guarantees the component is known before x-data is evaluated,
   regardless of script load order.
   Server-side validation/totals remain authoritative; this only
   drives the add/remove UI and the live on-screen totals.
─────────────────────────────────────────────────────────── */
document.addEventListener('alpine:init', () => {
    Alpine.data('workOrderForm', (initialParts, partsCatalog, initialLaborCost, serverErrors) => ({
        laborCost: initialLaborCost,
        catalog: partsCatalog,
        errors: serverErrors || {},
        _keyCounter: initialParts.length,
        parts: initialParts.map((row, i) => ({ _key: 'row-' + i, ...row })),

        blankRow() {
            const row = {
                _key: 'row-' + this._keyCounter,
                source: '', part_id: '', description: '',
                quantity: '1', unit_cost: '', unit_price: '', note: '',
            };
            this._keyCounter++;
            return row;
        },

        addRow() {
            this.parts.push(this.blankRow());
        },

        removeRow(index) {
            this.parts.splice(index, 1);
        },

        onSourceChange(row) {
            // Switching source invalidates whatever was picked before —
            // never leave a stale part_id/description hanging around.
            row.part_id = '';
            row.description = '';
            row.unit_cost = row.source === 'customer_supplied' ? '0' : '';
            row.unit_price = '';
        },

        onPartSelect(row) {
            const part = this.catalog[row.part_id];
            if (!part) return;
            row.unit_cost = part.purchase_price ?? 0;
            row.unit_price = part.sale_price ?? 0;
            if (!row.quantity) row.quantity = '1';
        },

        lineTotal(row) {
            return (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_price) || 0);
        },

        get partsTotal() {
            return this.parts.reduce((sum, row) => sum + this.lineTotal(row), 0);
        },

        get grandTotal() {
            return (parseFloat(this.laborCost) || 0) + this.partsTotal;
        },

        fmt(n) {
            return (n || 0).toLocaleString('el-GR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
        },

        errorFor(index, field) {
            const key = 'parts.' + index + '.' + field;
            return this.errors[key] ? this.errors[key][0] : null;
        },
    }));
});
</script>
@endpush
