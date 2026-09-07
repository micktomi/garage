@extends('layouts.workshop')

@section('title', 'Σάρωση Άδειας — Συνεργείο')
@section('header-title', 'Σάρωση Άδειας')
@section('header-back', route('workshop.dashboard'))

@php
    $extracted = $extracted ?? null;
    $timings = $timings ?? null;
    $reviewing = is_array($extracted) || old('review_ready') === '1';
    $fieldValue = fn (string $field) => old($field, is_array($extracted) ? ($extracted[$field] ?? '') : '');
@endphp

@push('styles')
<style>
    .scan-page { max-width: 760px; margin: 0 auto; display: grid; gap: 1rem; }
    .scan-heading { font-size: clamp(1.45rem, 5vw, 2rem); font-weight: 750; letter-spacing: -.035em; }
    .scan-lead { color: var(--text-muted); line-height: 1.55; }
    .scan-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    .scan-card-header { padding: .85rem 1rem; border-bottom: 1px solid var(--border); font-weight: 700; }
    .scan-card-body { padding: 1rem; display: grid; gap: 1rem; }
    .scan-upload { display: grid; place-items: center; gap: .8rem; min-height: 220px; padding: 1.25rem; border: 1px dashed var(--border); border-radius: var(--radius-sm); background: var(--surface-2); text-align: center; cursor: pointer; }
    .scan-upload svg { width: 44px; height: 44px; color: var(--accent); }
    .scan-upload strong { display: block; margin-bottom: .25rem; }
    .scan-upload span { color: var(--text-muted); font-size: .82rem; }
    .scan-preview { width: 100%; max-height: 360px; object-fit: contain; border-radius: var(--radius-sm); background: #090c10; }
    .scan-grid { display: grid; gap: .9rem; }
    .scan-field { display: grid; gap: .35rem; }
    .scan-field label { color: var(--text-muted); font-size: .8rem; font-weight: 650; }
    .scan-input { width: 100%; min-height: 44px; padding: .65rem .75rem; color: var(--text); background: var(--surface-3); border: 1px solid var(--border); border-radius: var(--radius-sm); outline: none; }
    .scan-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
    .scan-error { color: var(--danger); font-size: .75rem; }
    .scan-alert { padding: .8rem 1rem; border: 1px solid rgba(226,75,74,.35); border-radius: var(--radius-sm); background: var(--danger-dim); color: var(--danger); }
    .scan-review-note { padding: .8rem 1rem; border: 1px solid rgba(210,153,34,.35); border-radius: var(--radius-sm); background: rgba(210,153,34,.1); color: var(--text); line-height: 1.45; }
    .scan-timings { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; padding: .75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface-2); font-size: .75rem; }
    .scan-timings span { display: grid; gap: .15rem; color: var(--text-muted); }
    .scan-timings strong { color: var(--text); font-size: .85rem; }
    .scan-confirm { display: flex; align-items: flex-start; gap: .65rem; padding: .8rem; border: 1px solid rgba(210,153,34,.35); border-radius: var(--radius-sm); background: rgba(210,153,34,.1); line-height: 1.4; }
    .scan-confirm input { margin-top: .2rem; }
    .scan-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .65rem; }
    .scan-button { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; padding: .65rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border); font-weight: 700; cursor: pointer; }
    .scan-button--primary { color: var(--ws-primary-fg); background: var(--accent); border-color: var(--accent); }
    .scan-button--secondary { color: var(--text); background: var(--surface-2); }
    .scan-button[disabled] { opacity: .65; cursor: wait; }
    @media (min-width: 640px) {
        .scan-grid { grid-template-columns: 1fr 1fr; }
        .scan-field--wide { grid-column: 1 / -1; }
    }
</style>
@endpush

@section('content')
<div class="scan-page">
    <div>
        <h1 class="scan-heading">Σάρωση άδειας κυκλοφορίας</h1>
        <p class="scan-lead">Τράβηξε καθαρή φωτογραφία και έλεγξε όλα τα αναγνωρισμένα στοιχεία πριν αποθηκευτούν.</p>
    </div>

    @if($errors->any())
        <div class="scan-alert" role="alert">
            <strong>Χρειάζεται διόρθωση:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @unless($reviewing)
        <form
            method="POST"
            action="{{ route('workshop.registration-scan.extract') }}"
            enctype="multipart/form-data"
            class="scan-card"
            x-data="registrationScan()"
            x-on:submit="if (preparing) { $event.preventDefault() } else { submitting = true }"
        >
            @csrf
            <div class="scan-card-header">1. Φωτογραφία εγγράφου</div>
            <div class="scan-card-body">
                <label class="scan-upload" for="registration_image">
                    <template x-if="!preview">
                        <span>
                            <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5 8.1 5.7a1.5 1.5 0 0 1 1.2-.6h5.4a1.5 1.5 0 0 1 1.2.6l1.35 1.8h1.5a1.5 1.5 0 0 1 1.5 1.5v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V9a1.5 1.5 0 0 1 1.5-1.5h1.5Z"/>
                                <circle cx="12" cy="12.75" r="3.25"/>
                            </svg>
                            <strong>Άνοιγμα κάμερας ή επιλογή φωτογραφίας</strong>
                            <span>Άνοιξε πλήρως την άδεια · όλα τα τμήματα στο κάδρο · χωρίς αντανακλάσεις</span>
                        </span>
                    </template>
                    <img x-show="preview" x-bind:src="preview" class="scan-preview" alt="Προεπισκόπηση άδειας">
                </label>
                <input
                    id="registration_image"
                    name="registration_image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    capture="environment"
                    required
                    class="ws-sr-only"
                    x-on:change="preprocessImage($event)"
                >
                <input type="hidden" name="browser_preprocess_ms" x-bind:value="preprocessMs">
                <p class="scan-lead" x-show="preparing" x-cloak>Βελτιστοποίηση φωτογραφίας πριν την αποστολή…</p>
                <div class="scan-actions">
                    <button class="scan-button scan-button--primary" type="submit" x-bind:disabled="preparing || submitting">
                        <span x-show="!preparing && !submitting">Αναγνώριση στοιχείων</span>
                        <span x-show="preparing" x-cloak>Προετοιμασία εικόνας…</span>
                        <span x-show="submitting" x-cloak>Γίνεται αναγνώριση…</span>
                    </button>
                </div>
            </div>
        </form>
    @else
        <form method="POST" action="{{ route('workshop.registration-scan.store') }}" class="scan-card">
            @csrf
            <input type="hidden" name="review_ready" value="1">
            <div class="scan-card-header">2. Έλεγχος και αποθήκευση</div>
            <div class="scan-card-body">
                <div class="scan-review-note">
                    <strong>Απαραίτητος ανθρώπινος έλεγχος.</strong> Διόρθωσε ό,τι δεν διαβάστηκε σωστά. Η φωτογραφία δεν αποθηκεύεται.
                </div>

                @if(is_array($timings))
                    <div
                        class="scan-timings"
                        data-scan-timings
                        data-server-ms="{{ $timings['server_total_ms'] }}"
                        aria-label="Χρόνοι αναγνώρισης"
                    >
                        <span>Προετοιμασία browser <strong>{{ number_format($timings['browser_preprocess_ms'], 1) }} ms</strong></span>
                        <span>Αίτημα / upload <strong data-request-upload-ms>—</strong></span>
                        <span>Gemini API <strong>{{ number_format($timings['gemini_ms'], 1) }} ms</strong></span>
                        <span>Decode / validation <strong>{{ number_format($timings['decode_validation_ms'], 1) }} ms</strong></span>
                    </div>
                @endif

                <div class="scan-grid">
                    <div class="scan-field scan-field--wide">
                        <label for="owner_full_name">Ονοματεπώνυμο / επωνυμία κατόχου *</label>
                        <input class="scan-input" id="owner_full_name" name="owner_full_name" value="{{ $fieldValue('owner_full_name') }}" required>
                        @error('owner_full_name') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="scan-field">
                        <label for="afm">ΑΦΜ</label>
                        <input class="scan-input" id="afm" name="afm" inputmode="numeric" maxlength="9" value="{{ $fieldValue('afm') }}">
                        @error('afm') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="scan-field">
                        <label for="phone">Τηλέφωνο</label>
                        <input class="scan-input" id="phone" name="phone" inputmode="tel" value="{{ $fieldValue('phone') }}">
                        @error('phone') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="scan-field scan-field--wide">
                        <label for="address">Διεύθυνση</label>
                        <input class="scan-input" id="address" name="address" value="{{ $fieldValue('address') }}">
                        @error('address') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="scan-field">
                        <label for="plate_number">Πινακίδα *</label>
                        <input class="scan-input" id="plate_number" name="plate_number" maxlength="20" value="{{ $fieldValue('plate_number') }}" required>
                        @error('plate_number') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="scan-field">
                        <label for="vin">VIN / αριθμός πλαισίου</label>
                        <input class="scan-input" id="vin" name="vin" maxlength="50" value="{{ $fieldValue('vin') }}">
                        @error('vin') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="scan-field">
                        <label for="make">Μάρκα</label>
                        <input class="scan-input" id="make" name="make" value="{{ $fieldValue('make') }}">
                    </div>
                    <div class="scan-field">
                        <label for="model">Μοντέλο</label>
                        <input class="scan-input" id="model" name="model" value="{{ $fieldValue('model') }}">
                    </div>
                    <div class="scan-field">
                        <label for="fuel">Καύσιμο</label>
                        <input class="scan-input" id="fuel" name="fuel" value="{{ $fieldValue('fuel') }}">
                    </div>
                    <div class="scan-field">
                        <label for="engine_cc">Κυβισμός (cc)</label>
                        <input class="scan-input" id="engine_cc" name="engine_cc" type="number" min="1" max="20000" value="{{ $fieldValue('engine_cc') }}">
                        @error('engine_cc') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="scan-field">
                        <label for="first_registered_at">Πρώτη κυκλοφορία</label>
                        <input class="scan-input" id="first_registered_at" name="first_registered_at" type="date" value="{{ $fieldValue('first_registered_at') }}">
                        @error('first_registered_at') <span class="scan-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if(($errors->has('near_vin') || old('confirm_near_vin')) && ! $errors->has('plate_number') && ! $errors->has('vin'))
                    <label class="scan-confirm">
                        <input type="checkbox" name="confirm_near_vin" value="1" @checked(old('confirm_near_vin'))>
                        <span><strong>Επιβεβαιώνω τον έλεγχο του VIN.</strong> Έλεγξα ξανά το πεδίο (E) της άδειας και πρόκειται για διαφορετικό όχημα, παρότι υπάρχει VIN με απόσταση 1–2 χαρακτήρων.</span>
                    </label>
                @endif

                <div class="scan-actions">
                    <a class="scan-button scan-button--secondary" href="{{ route('workshop.registration-scan.show') }}">Νέα φωτογραφία</a>
                    <button class="scan-button scan-button--primary" type="submit">Αποθήκευση πελάτη και οχήματος</button>
                </div>
            </div>
        </form>
    @endunless
</div>
@endsection

@push('scripts')
<script>
    window.registrationScan = () => ({
        preview: null,
        submitting: false,
        preparing: false,
        preprocessMs: null,

        async preprocessImage(event) {
            const input = event.target;
            const original = input.files?.[0] ?? null;

            if (! original) {
                this.replacePreview(null);
                this.preprocessMs = null;

                return;
            }

            const startedAt = performance.now();
            this.preparing = true;

            try {
                const decoded = await this.decodeImage(original);
                const maxDimension = 3072;
                const scale = Math.min(1, maxDimension / Math.max(decoded.width, decoded.height));
                const width = Math.max(1, Math.round(decoded.width * scale));
                const height = Math.max(1, Math.round(decoded.height * scale));
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const context = canvas.getContext('2d');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, width, height);
                context.drawImage(decoded.source, 0, 0, width, height);
                decoded.cleanup();

                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));

                if (! blob || typeof DataTransfer === 'undefined') {
                    throw new Error('Browser image preprocessing unavailable.');
                }

                const processed = new File([blob], 'registration-scan.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });
                const transfer = new DataTransfer();
                transfer.items.add(processed);
                input.files = transfer.files;
                this.replacePreview(URL.createObjectURL(processed));
            } catch (error) {
                this.replacePreview(URL.createObjectURL(original));
            } finally {
                this.preprocessMs = Math.round((performance.now() - startedAt) * 10) / 10;
                this.preparing = false;
            }
        },

        async decodeImage(file) {
            if (typeof createImageBitmap === 'function') {
                try {
                    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });

                    return {
                        source: bitmap,
                        width: bitmap.width,
                        height: bitmap.height,
                        cleanup: () => bitmap.close(),
                    };
                } catch (error) {
                    // Continue with the browser image decoder fallback.
                }
            }

            const objectUrl = URL.createObjectURL(file);
            const image = await new Promise((resolve, reject) => {
                const element = new Image();
                element.onload = () => resolve(element);
                element.onerror = reject;
                element.src = objectUrl;
            });

            return {
                source: image,
                width: image.naturalWidth,
                height: image.naturalHeight,
                cleanup: () => URL.revokeObjectURL(objectUrl),
            };
        },

        replacePreview(url) {
            if (this.preview) {
                URL.revokeObjectURL(this.preview);
            }

            this.preview = url;
        },
    });

    document.addEventListener('DOMContentLoaded', () => {
        const timings = document.querySelector('[data-scan-timings]');
        const navigation = performance.getEntriesByType('navigation')[0];

        if (! timings || ! navigation) {
            return;
        }

        const serverMs = Number(timings.dataset.serverMs || 0);
        const requestUploadMs = Math.max(0, navigation.responseStart - navigation.requestStart - serverMs);
        const output = timings.querySelector('[data-request-upload-ms]');

        if (output) {
            output.textContent = requestUploadMs.toFixed(1) + ' ms (περ.)';
        }
    });
</script>
@endpush
