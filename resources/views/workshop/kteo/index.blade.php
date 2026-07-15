@extends('layouts.workshop')

@section('title', 'ΚΤΕΟ — ληγμένα & επερχόμενα — Συνεργείο')
@section('header-title', 'ΚΤΕΟ — ληγμένα & επερχόμενα')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────────────────── */
    .kt-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.25rem 0 1.25rem;
    }
    .kt-page-title {
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.2;
    }
    .kt-count-badge {
        background: var(--danger-dim);
        color: var(--danger);
        border: 1px solid rgba(248,81,73,0.25);
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
    }
    .kt-count-badge.kt-count-badge--ok {
        background: var(--success-dim);
        color: var(--success);
        border-color: rgba(63,185,80,0.25);
    }

    /* ── List / card ─────────────────────────────────────────── */
    .kt-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .kt-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .kt-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .kt-plate {
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

    .kt-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .kt-badge::before {
        content: '';
        width: 5px;
        height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .kt-badge--expired { background: var(--danger-dim); color: var(--danger); }
    .kt-badge--expired::before { background: var(--danger); }
    .kt-badge--soon { background: var(--accent-dim); color: var(--accent); }
    .kt-badge--soon::before { background: var(--accent); }

    .kt-card-main {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .kt-card-vehicle {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.3;
    }
    .kt-card-customer {
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    .kt-card-phone {
        font-size: 0.8125rem;
        color: var(--info);
        text-decoration: none;
    }
    .kt-card-phone:hover { text-decoration: underline; }

    .kt-card-divider {
        height: 1px;
        background: var(--border);
        margin: 0.125rem 0;
    }

    .kt-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .kt-card-date {
        font-size: 0.75rem;
        color: var(--text-faint);
    }
    .kt-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        justify-content: flex-end;
    }
    .kt-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-muted);
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.35rem 0.75rem;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
        white-space: nowrap;
        cursor: pointer;
        font-family: inherit;
    }
    .kt-btn-action:hover {
        color: var(--text);
        border-color: var(--text-faint);
        background: var(--surface-3);
    }
    .kt-btn-action svg { width: 13px; height: 13px; stroke-width: 2.5; flex-shrink: 0; }
    .kt-btn-call { color: var(--success); border-color: rgba(63,185,80,.35); background: var(--success-dim); }
    .kt-btn-call:hover { background: rgba(63,185,80,.22); }
    .kt-btn-sms { color: var(--info); border-color: rgba(88,166,255,.35); background: var(--info-dim); }
    .kt-btn-sms:hover { background: rgba(88,166,255,.22); }
    .kt-btn-copy.is-copied {
        color: var(--success);
        border-color: rgba(63,185,80,.4);
        background: var(--success-dim);
    }
    .kt-no-phone {
        font-size: 0.75rem;
        color: var(--text-faint);
        font-style: italic;
    }

    /* Empty state */
    .kt-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }
    .kt-empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 1rem;
        color: var(--text-faint);
    }
    .kt-empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.25rem;
    }
    .kt-empty-sub {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    /* Desktop enhancements */
    @media (min-width: 768px) {
        .kt-page-title { font-size: 2rem; }

        .kt-card {
            flex-direction: row;
            align-items: center;
            gap: 1.25rem;
            padding: 1.125rem 1.375rem;
        }
        .kt-card-top { flex-direction: column-reverse; align-items: flex-start; flex-shrink: 0; width: 130px; gap: 0.375rem; }
        .kt-card-main { flex: 1; min-width: 0; }
        .kt-card-divider { display: none; }
        .kt-card-footer { flex-direction: column; align-items: flex-end; flex-shrink: 0; gap: 0.5rem; }
    }
</style>
@endpush

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
