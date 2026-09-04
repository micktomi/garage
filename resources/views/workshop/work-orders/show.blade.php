@extends('layouts.workshop')

@section('title', 'Εντολή #' . str_pad($workOrder->id, 4, '0', STR_PAD_LEFT) . ' — Συνεργείο')
@section('header-title', 'Εντολή #' . str_pad($workOrder->id, 4, '0', STR_PAD_LEFT))
@section('header-back', route('workshop.work-orders.index'))



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
                        @cannot('amendCompleted', $workOrder)
                            {{-- Ο server απαντά ούτως ή άλλως 403 (WorkOrderPolicy::amendCompleted).
                                 Εδώ απλώς δεν προσφέρονται κουμπιά που οδηγούν σε αδιέξοδο. --}}
                            <span
                                class="wos-status-btn wos-status-btn--{{ $workOrder->status->value }} is-current"
                                aria-current="true"
                                aria-label="Τρέχουσα κατάσταση: {{ $workOrder->status->label() }}"
                            >
                                {{ $workOrder->status->label() }}
                            </span>
                            <p class="wos-status-locked">Η εντολή έχει ολοκληρωθεί. Η τροποποίησή της γίνεται μόνο από τον ιδιοκτήτη.</p>
                        @else
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
                                    <input type="hidden" name="lock_version" value="{{ $workOrder->lock_version }}">
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
                                        {{-- Το κενό option είναι επιλεγμένο εξ ορισμού για δύο
                                             λόγους: όσο το παραστατικό δεν είναι «Χωρίς
                                             παραστατικό» το control υποβάλλει κενή τιμή αντί για
                                             αυθαίρετη φορολογική αιτιολογία (το `hidden` κρύβει,
                                             δεν εμποδίζει την υποβολή), και όταν είναι, η
                                             αιτιολογία επιλέγεται ρητά αντί να προεπιλέγεται.
                                             Σκόπιμα HTML και όχι `disabled` μέσω του onchange: θα
                                             έκανε την ολοκλήρωση χωρίς παραστατικό να εξαρτάται
                                             από το να τρέξει το script. --}}
                                        <select
                                            id="wos-non-issue-reason-select-{{ $workOrder->id }}"
                                            name="non_issue_reason"
                                            class="wos-closure-select"
                                        >
                                            <option value="" selected>Επιλέξτε αιτιολογία…</option>
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
                                    <input type="hidden" name="lock_version" value="{{ $workOrder->lock_version }}">
                                    <input type="hidden" name="status" value="{{ $status->value }}">
                                    <button type="submit" class="wos-status-btn wos-status-btn--{{ $status->value }}">
                                        {{ $status->label() }}
                                    </button>
                                </form>
                            @endif
                        @endforeach
                        @endcannot
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
