@extends('layouts.workshop')

@section('title', 'Νέα Εντολή Εργασίας — Συνεργείο')
@section('header-title', 'Νέα Εντολή')

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
    .woc-select,
    .woc-textarea {
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
    .woc-textarea {
        resize: vertical;
        min-height: 110px;
        line-height: 1.6;
    }
    .woc-input::placeholder,
    .woc-textarea::placeholder { color: var(--text-faint); }
    .woc-input:focus,
    .woc-select:focus,
    .woc-textarea:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .woc-input.is-invalid,
    .woc-select.is-invalid,
    .woc-textarea.is-invalid {
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
    /* checked states per source */
    .woc-source-pill[value="from_stock"]:checked    + .woc-source-label { background: var(--accent-dim);  border-color: rgba(245,158,11,.4); color: var(--accent); }
    .woc-source-pill[value="customer_supplied"]:checked + .woc-source-label { background: var(--success-dim); border-color: rgba(63,185,80,.4);  color: var(--success); }
    .woc-source-pill[value="purchased_for_job"]:checked  + .woc-source-label { background: var(--info-dim);    border-color: rgba(88,166,255,.4); color: var(--info); }

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
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: var(--text);
        min-width: 90px;
        text-align: right;
        font-size: 0.9375rem;
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
        font-weight: 700;
        font-size: 0.9375rem;
        color: var(--text);
        background: var(--surface);
    }
    .woc-sum-val { font-weight: 600; font-variant-numeric: tabular-nums; }
    .woc-sum-row--grand .woc-sum-val { color: var(--accent); }

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

<h1 class="woc-page-title">Νέα Εντολή Εργασίας</h1>

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

<form method="POST" action="{{ route('workshop.work-orders.store') }}" novalidate>
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) Str::uuid()) }}">

    {{-- ── Πελάτης & Όχημα ────────────────────────────────────── --}}
    <div class="woc-card">
        <div class="woc-card-title">Πελάτης &amp; Όχημα</div>
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
    </div>

    {{-- ── Πρόβλημα / Εργασία ──────────────────────────────────── --}}
    <div class="woc-card">
        <div class="woc-card-title">Περιγραφή Εργασίας</div>
        <div class="woc-card-body">
            <div class="woc-field">
                <label for="problem_description" class="woc-label">
                    Πρόβλημα / Εργασία <span class="woc-req">*</span>
                </label>
                <textarea id="problem_description" name="problem_description" rows="4"
                    class="woc-textarea {{ $errors->has('problem_description') ? 'is-invalid' : '' }}"
                    placeholder="Περιγράψτε το πρόβλημα ή την εργασία που θα εκτελεστεί…">{{ old('problem_description') }}</textarea>
                @error('problem_description') <span class="woc-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    {{-- ── Κόστος Εργασίας ─────────────────────────────────────── --}}
    <div class="woc-card">
        <div class="woc-card-title">Κόστος Εργασίας</div>
        <div class="woc-card-body">
            <div class="woc-field" style="max-width:220px;">
                <label for="labor_cost" class="woc-label">Αμοιβή εργασίας</label>
                <div class="woc-eur">
                    <input type="number" id="labor_cost" name="labor_cost"
                        class="woc-input {{ $errors->has('labor_cost') ? 'is-invalid' : '' }}"
                        value="{{ old('labor_cost', '0') }}"
                        min="0" step="0.01" placeholder="0.00"
                        oninput="recalc()">
                    <span class="woc-eur-sym">€</span>
                </div>
                @error('labor_cost') <span class="woc-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    {{-- ── Ανταλλακτικό / Υλικό ────────────────────────────────── --}}
    <div class="woc-card">
        <div class="woc-card-title">Ανταλλακτικό / Υλικό</div>
        <div class="woc-card-body">

            {{-- Source radio pills --}}
            <div class="woc-field">
                <label class="woc-label">Προέλευση</label>
                <div class="woc-source-wrap">

                    <input type="radio" name="part[source]" id="src-stock"
                           class="woc-source-pill" value="from_stock"
                           {{ old('part.source') === 'from_stock' ? 'checked' : '' }}>
                    <label for="src-stock" class="woc-source-label">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                        </svg>
                        Από απόθεμα
                    </label>

                    <input type="radio" name="part[source]" id="src-cust"
                           class="woc-source-pill" value="customer_supplied"
                           {{ old('part.source') === 'customer_supplied' ? 'checked' : '' }}>
                    <label for="src-cust" class="woc-source-label">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        Το έφερε ο πελάτης
                    </label>

                    <input type="radio" name="part[source]" id="src-job"
                           class="woc-source-pill" value="purchased_for_job"
                           {{ old('part.source') === 'purchased_for_job' ? 'checked' : '' }}>
                    <label for="src-job" class="woc-source-label">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                        </svg>
                        Αγοράστηκε για εργασία
                    </label>

                </div>
                <span class="woc-hint">Αφήστε χωρίς επιλογή αν δεν υπάρχει ανταλλακτικό.</span>
            </div>

            {{-- ── Conditional fields ────────────────────────────── --}}
            <div class="woc-part-fields" id="part-fields">

                {{-- Part dropdown — only for from_stock --}}
                <div id="field-part-id" style="display:none;">
                    <div class="woc-field">
                        <label for="part_id_sel" class="woc-label">
                            Ανταλλακτικό <span class="woc-req">*</span>
                        </label>
                        <select id="part_id_sel" name="part[part_id]"
                            class="woc-select {{ $errors->has('part.part_id') ? 'is-invalid' : '' }}"
                            onchange="autofillPrice()">
                            <option value="">— Επιλογή από απόθεμα —</option>
                            @foreach($parts as $p)
                                <option value="{{ $p->id }}"
                                    data-price="{{ $p->sale_price ?? 0 }}"
                                    {{ old('part.part_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                    @if($p->quantity !== null) (αποθ: {{ $p->quantity }}) @endif
                                    @if($p->sale_price) — {{ number_format($p->sale_price, 2, ',', '.') }} € @endif
                                </option>
                            @endforeach
                        </select>
                        @error('part.part_id') <span class="woc-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Description — for non-stock --}}
                <div id="field-description" style="display:none;">
                    <div class="woc-field">
                        <label for="part_desc" class="woc-label">
                            Περιγραφή <span class="woc-req">*</span>
                        </label>
                        <input type="text" id="part_desc" name="part[description]"
                            class="woc-input {{ $errors->has('part.description') ? 'is-invalid' : '' }}"
                            value="{{ old('part.description') }}"
                            placeholder="π.χ. Λάδι κινητήρα 5W-40…"
                            oninput="recalc()">
                        @error('part.description') <span class="woc-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Qty / unit_cost / unit_price grid --}}
                <div class="woc-part-grid">

                    <div class="woc-field">
                        <label for="part_qty" class="woc-label">Ποσότητα <span class="woc-req">*</span></label>
                        <input type="number" id="part_qty" name="part[quantity]"
                            class="woc-input {{ $errors->has('part.quantity') ? 'is-invalid' : '' }}"
                            value="{{ old('part.quantity', 1) }}"
                            min="0.001" step="1" placeholder="1"
                            oninput="recalc()">
                        @error('part.quantity') <span class="woc-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- unit_cost: hidden for customer_supplied --}}
                    <div class="woc-field" id="field-unit-cost">
                        <label for="part_cost" class="woc-label">Κόστος / τεμ.</label>
                        <div class="woc-eur">
                            <input type="number" id="part_cost" name="part[unit_cost]"
                                class="woc-input {{ $errors->has('part.unit_cost') ? 'is-invalid' : '' }}"
                                value="{{ old('part.unit_cost', '') }}"
                                min="0" step="0.01" placeholder="0.00">
                            <span class="woc-eur-sym">€</span>
                        </div>
                        @error('part.unit_cost') <span class="woc-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="woc-field">
                        <label for="part_price" class="woc-label">Τιμή / τεμ.</label>
                        <div class="woc-eur">
                            <input type="number" id="part_price" name="part[unit_price]"
                                class="woc-input {{ $errors->has('part.unit_price') ? 'is-invalid' : '' }}"
                                value="{{ old('part.unit_price', '') }}"
                                min="0" step="0.01" placeholder="0.00"
                                oninput="recalc()">
                            <span class="woc-eur-sym">€</span>
                        </div>
                        @error('part.unit_price') <span class="woc-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Subtotal (read-only display) --}}
                    <div class="woc-field">
                        <label class="woc-label">Σύνολο γραμμής</label>
                        <div class="woc-eur">
                            <input type="text" id="part_line_total" class="woc-input"
                                value="0,00" readonly tabindex="-1"
                                style="color:var(--accent);font-weight:700;background:var(--surface-2);cursor:default;">
                            <span class="woc-eur-sym">€</span>
                        </div>
                    </div>

                </div>

            </div>{{-- /part-fields --}}

        </div>
    </div>

    {{-- ── Σύνοψη ───────────────────────────────────────────────── --}}
    <div class="woc-card">
        <div class="woc-card-title">Σύνοψη Κόστους</div>
        <div class="woc-card-body" style="padding-top:0.625rem;padding-bottom:0.625rem;">
            <div class="woc-summary">
                <div class="woc-sum-row">
                    <span>Αμοιβή εργασίας</span>
                    <span class="woc-sum-val" id="sum-labor">0,00 €</span>
                </div>
                <div class="woc-sum-row">
                    <span>Ανταλλακτικά</span>
                    <span class="woc-sum-val" id="sum-parts">0,00 €</span>
                </div>
                <div class="woc-sum-row woc-sum-row--grand">
                    <span>Σύνολο</span>
                    <span class="woc-sum-val" id="sum-total">0,00 €</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Submit ───────────────────────────────────────────────── --}}
    <div class="woc-submit-row">
        <a href="{{ route('workshop.work-orders.index') }}" class="woc-cancel-btn">Άκυρο</a>
        <button type="submit" class="woc-submit-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Δημιουργία Εντολής
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script>
/* ────────────────────────────────────────────────────────────
   Vehicle dropdown — scoped to the selected customer
─────────────────────────────────────────────────────────── */
const VEHICLES_BY_CUSTOMER = @json($vehiclesByCustomer);

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
}

document.getElementById('customer_id').addEventListener('change', function () {
    populateVehicles(this.value, null);
});

/* ────────────────────────────────────────────────────────────
   Source radio → show/hide conditional fields
─────────────────────────────────────────────────────────── */
const SOURCES = ['src-stock', 'src-cust', 'src-job'];

function applySource(value) {
    const pf    = document.getElementById('part-fields');
    const fdPid = document.getElementById('field-part-id');
    const fdDsc = document.getElementById('field-description');
    const fdCst = document.getElementById('field-unit-cost');

    if (!value) {
        pf.classList.remove('is-visible');
        return;
    }

    pf.classList.add('is-visible');

    if (value === 'from_stock') {
        fdPid.style.display = '';
        fdDsc.style.display = 'none';
        fdCst.style.display = '';
    } else if (value === 'customer_supplied') {
        fdPid.style.display = 'none';
        fdDsc.style.display = '';
        fdCst.style.display = 'none';
        // unit_cost is implicitly 0 for customer_supplied
        document.getElementById('part_cost').value = '0';
    } else { // purchased_for_job
        fdPid.style.display = 'none';
        fdDsc.style.display = '';
        fdCst.style.display = '';
    }

    recalc();
}

SOURCES.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => applySource(el.value));
});

/* ────────────────────────────────────────────────────────────
   Auto-fill unit_price from selected Part's sale_price
─────────────────────────────────────────────────────────── */
function autofillPrice() {
    const sel   = document.getElementById('part_id_sel');
    const opt   = sel?.options[sel.selectedIndex];
    const price = parseFloat(opt?.dataset?.price || 0);
    const inp   = document.getElementById('part_price');
    if (inp && price > 0 && !inp.value) {
        inp.value = price.toFixed(2);
    }
    recalc();
}

/* ────────────────────────────────────────────────────────────
   Live recalc — line total + summary
─────────────────────────────────────────────────────────── */
function recalc() {
    const labor = Math.max(0, parseFloat(document.getElementById('labor_cost')?.value || 0) || 0);
    const qty   = Math.max(0, parseFloat(document.getElementById('part_qty')?.value   || 0) || 0);
    const price = Math.max(0, parseFloat(document.getElementById('part_price')?.value || 0) || 0);

    // only count parts if the fields are visible
    const partsVisible = document.getElementById('part-fields')?.classList.contains('is-visible');
    const lineTotal = partsVisible ? qty * price : 0;

    // update line total display
    const lt = document.getElementById('part_line_total');
    if (lt) lt.value = fmt(lineTotal);

    // update summary
    setText('sum-labor', fmt(labor));
    setText('sum-parts', fmt(lineTotal));
    setText('sum-total', fmt(labor + lineTotal));
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function fmt(n) {
    return n.toLocaleString('el-GR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
}

/* ── Init ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    // Restore vehicle dropdown scoped to the old (or query-string) customer_id, if any
    const customerSel = document.getElementById('customer_id');
    populateVehicles(customerSel.value, @json(old('vehicle_id', request()->query('vehicle_id'))));

    // Restore state from old() on validation failure
    const checked = document.querySelector('.woc-source-pill:checked');
    if (checked) applySource(checked.value);
    recalc();

    // wire labor_cost and part_price to recalc (already via oninput attr, but defensive)
    ['labor_cost','part_price','part_qty'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', recalc);
    });
});
</script>
@endpush
