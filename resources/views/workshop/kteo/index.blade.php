@extends('layouts.workshop')

@section('title', 'ΚΤΕΟ — ληγμένα & επερχόμενα — Συνεργείο')
@section('header-title', 'ΚΤΕΟ — ληγμένα & επερχόμενα')


@section('content')

{{-- Page header --}}
<div class="kt-page-header">
    <div>
        <div class="ws-greeting-date" style="font-size:0.75rem;color:var(--text-faint);margin-bottom:0.2rem;">
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
        <h1 class="kt-page-title">ΚΤΕΟ — ληγμένα & επερχόμενα</h1>
    </div>
    <span class="kt-count-badge {{ $vehicles->isEmpty() ? 'kt-count-badge--ok' : '' }}">{{ $vehicles->count() }}</span>
</div>

@if($vehicles->isEmpty())
    <div class="kt-empty">
        <svg class="kt-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        <div class="kt-empty-title">Δεν υπάρχουν ληγμένα ή επερχόμενα ΚΤΕΟ</div>
        <div class="kt-empty-sub">Κανένα όχημα δεν έχει λήξει ή λήγει εντός 30 ημερών.</div>
    </div>
@else
    <div class="kt-list">
        @foreach($vehicles as $vehicle)
            @php
                $daysLeft = $today->diffInDays($vehicle->kteo_expires_at, false);
                $isExpired = $daysLeft < 0;
                $phone = $vehicle->customer?->phone;
                $statusPhrase = $isExpired
                    ? 'έχει λήξει'
                    : 'λήγει στις ' . $vehicle->kteo_expires_at->format('d/m/Y');
                $smsMessage = 'Καλησπέρα σας. Σας ενημερώνουμε ότι το ΚΤΕΟ του οχήματός σας με πινακίδα '
                    . ($vehicle->plate_number ?? '—') . ' ' . $statusPhrase
                    . '. Επικοινωνήστε μαζί μας για τον προγραμματισμό του ελέγχου.';
            @endphp
            <div class="kt-card">
                <div class="kt-card-top">
                    <span class="kt-plate">{{ $vehicle->plate_number ?? '—' }}</span>
                    @if($isExpired)
                        <span class="kt-badge kt-badge--expired">Ληγμένο</span>
                    @else
                        <span class="kt-badge kt-badge--soon">{{ $daysLeft }} {{ $daysLeft == 1 ? 'ημέρα' : 'ημέρες' }}</span>
                    @endif
                </div>

                <div class="kt-card-main">
                    <div class="kt-card-vehicle">
                        {{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) ?: '—' }}
                    </div>
                    <div class="kt-card-customer">{{ $vehicle->customer?->full_name ?? '—' }}</div>
                    @if($phone)
                        <a href="tel:{{ $phone }}" class="kt-card-phone">{{ $phone }}</a>
                    @endif
                </div>

                <div class="kt-card-divider"></div>

                <div class="kt-card-footer">
                    <span class="kt-card-date">ΚΤΕΟ: {{ $vehicle->kteo_expires_at->translatedFormat('d M Y') }}</span>

                    <div class="kt-actions">
                        @if($phone)
                            <a href="tel:{{ $phone }}" class="kt-btn-action kt-btn-call">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                                </svg>
                                Κλήση
                            </a>
                            <a href="sms:{{ $phone }}?body={{ rawurlencode($smsMessage) }}" class="kt-btn-action kt-btn-sms">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                                </svg>
                                Αποστολή SMS
                            </a>
                            <button type="button" class="kt-btn-action kt-btn-copy">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.181 1.1.124 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.061 1.907-2.185a48.208 48.208 0 0 1 1.927-.181"/>
                                </svg>
                                <span class="kt-btn-copy-label">Αντιγραφή μηνύματος</span>
                            </button>
                            <script type="application/json" class="kt-msg-json">@json($smsMessage)</script>
                        @else
                            <span class="kt-no-phone">Χωρίς καταχωρημένο τηλέφωνο</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('.kt-btn-copy').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        var card = btn.closest('.kt-card');
        var jsonEl = card ? card.querySelector('.kt-msg-json') : null;
        if (!jsonEl) return;

        var text;
        try {
            text = JSON.parse(jsonEl.textContent);
        } catch (e) {
            return;
        }

        var copied = false;
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                copied = true;
            } catch (e) {
                copied = false;
            }
        }

        if (!copied) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
                copied = true;
            } catch (e) {
                copied = false;
            }
            document.body.removeChild(ta);
        }

        if (!copied) return;

        var label = btn.querySelector('.kt-btn-copy-label');
        var original = label ? label.textContent : null;
        btn.classList.add('is-copied');
        if (label) label.textContent = 'Αντιγράφηκε!';

        window.clearTimeout(btn._resetTimer);
        btn._resetTimer = window.setTimeout(function () {
            btn.classList.remove('is-copied');
            if (label && original) label.textContent = original;
        }, 1800);
    });
});
</script>
@endpush
