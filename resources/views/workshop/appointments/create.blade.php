@extends('layouts.workshop')

@section('title', 'Νέο Ραντεβού — Συνεργείο')
@section('header-title', 'Νέο Ραντεβού')

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
        min-height: 90px;
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
    <a href="{{ route('workshop.appointments.index') }}" class="woc-back-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
        </svg>
        Ραντεβού
    </a>
</div>

<h1 class="woc-page-title">Νέο Ραντεβού</h1>

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

<form method="POST" action="{{ route('workshop.appointments.store') }}" novalidate>
    @csrf

    <div class="woc-card">
        <div class="woc-card-title">Πελάτης &amp; Όχημα</div>
        <div class="woc-card-body woc-card-body--grid">

            <div class="woc-field">
                <label for="customer_id" class="woc-label">Πελάτης <span class="woc-req">*</span></label>
                <select id="customer_id" name="customer_id"
                    class="woc-select {{ $errors->has('customer_id') ? 'is-invalid' : '' }}">
                    <option value="">— Επιλογή πελάτη —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
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

    <div class="woc-card">
        <div class="woc-card-title">Ημερομηνία &amp; Ώρα</div>
        <div class="woc-card-body woc-card-body--grid">

            <div class="woc-field">
                <label for="appointment_date" class="woc-label">Ημερομηνία <span class="woc-req">*</span></label>
                <input type="date" id="appointment_date" name="appointment_date"
                    class="woc-input {{ $errors->has('appointment_date') ? 'is-invalid' : '' }}"
                    value="{{ old('appointment_date') }}">
                @error('appointment_date') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

            <div class="woc-field">
                <label for="appointment_time" class="woc-label">Ώρα <span class="woc-req">*</span></label>
                <input type="time" id="appointment_time" name="appointment_time"
                    class="woc-input {{ $errors->has('appointment_time') ? 'is-invalid' : '' }}"
                    value="{{ old('appointment_time') }}">
                @error('appointment_time') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

            <div class="woc-field span2">
                <label for="description" class="woc-label">Σημειώσεις / Λόγος επίσκεψης</label>
                <textarea id="description" name="description" rows="3"
                    class="woc-textarea {{ $errors->has('description') ? 'is-invalid' : '' }}"
                    placeholder="π.χ. Αλλαγή λαδιών, έλεγχος φρένων…">{{ old('description') }}</textarea>
                @error('description') <span class="woc-error">{{ $message }}</span> @enderror
            </div>

        </div>
    </div>

    <div class="woc-submit-row">
        <a href="{{ route('workshop.appointments.index') }}" class="woc-cancel-btn">Άκυρο</a>
        <button type="submit" class="woc-submit-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Καταχώρηση Ραντεβού
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script>
/* ────────────────────────────────────────────────────────────
   Vehicle dropdown — scoped to the selected customer
   (same behaviour as the work order create form)
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

document.addEventListener('DOMContentLoaded', function () {
    const customerSel = document.getElementById('customer_id');
    populateVehicles(customerSel.value, @json(old('vehicle_id')));
});
</script>
@endpush
