@extends('layouts.workshop')

@section('title', 'Εργασίες — Συνεργείο')

@push('styles')
<style>
    .wol-page,
    .wol-grid,
    .wol-card,
    .wol-card-person,
    .wol-card-problem {
        min-width: 0;
    }

    .wol-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
        overflow: visible;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .wol-section-heading {
        min-height: 32px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .wol-card {
        min-height: 260px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .wol-card-top,
    .wol-card-meta,
    .wol-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .wol-card-top .ws-status-badge {
        max-width: 210px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wol-card-person {
        display: flex;
        flex-direction: column;
    }

    .wol-card-person .ws-recent-order-customer,
    .wol-card-person .ws-recent-order-meta {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .wol-card-meta {
        justify-content: flex-start;
    }

    .wol-card-problem {
        padding-top: 14px;
        flex: 1;
        border-top: 1px solid var(--ws-border);
    }

    .wol-card-problem .ws-recent-order-meta {
        white-space: normal;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .wol-card-footer {
        margin-top: auto;
        color: var(--ws-text-muted);
    }

    .wol-card-chevron {
        width: 17px;
        height: 17px;
        flex: 0 0 17px;
        color: var(--ws-text-faint);
        stroke-width: 1.8;
        transition: color 140ms ease, transform 140ms ease;
    }

    .wol-card:hover .wol-card-chevron,
    .wol-card:focus-visible .wol-card-chevron {
        color: var(--ws-primary);
        transform: translateX(2px);
    }

    .wol-grid > .ws-empty-state {
        min-height: 180px;
    }

    @media (min-width: 1024px) {
        .wol-grid {
            display: block;
            overflow: hidden;
            border: 1px solid var(--ws-border);
            border-radius: 14px;
            background: var(--ws-card);
            box-shadow: var(--shadow-sm);
        }

        .wol-card {
            width: 100%;
            height: 72px;
            min-height: 72px;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 58px minmax(112px, 148px) 90px minmax(132px, .9fr) minmax(128px, 1.4fr) 78px 16px;
            align-items: center;
            gap: 10px;
            border: 0;
            border-bottom: 1px solid var(--ws-border);
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .wol-card:last-child {
            border-bottom: 0;
        }

        .wol-card:hover {
            border-color: var(--ws-border);
            background: var(--ws-sunken);
            box-shadow: none;
            transform: none;
        }

        .wol-card:focus-visible {
            position: relative;
            z-index: 1;
            border-color: var(--ws-primary);
            outline: 2px solid var(--ws-primary);
            outline-offset: -2px;
        }

        .wol-card-top,
        .wol-card-footer {
            display: contents;
        }

        .wol-card-top .ws-recent-order-number {
            grid-area: auto;
            grid-column: 1;
            grid-row: 1;
            align-self: center;
        }

        .wol-card-top .ws-status-badge {
            padding: 3px 6px;
            width: max-content;
            max-width: 100%;
            grid-column: 2;
            grid-row: 1;
            justify-self: start;
            font-size: 11px;
            white-space: nowrap;
        }

        .wol-card-meta {
            grid-column: 3;
            grid-row: 1;
        }

        .wol-card-person {
            grid-column: 4;
            grid-row: 1;
        }

        .wol-card-person .ws-recent-order-customer,
        .wol-card-person .ws-recent-order-meta {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wol-card-problem {
            padding: 0;
            grid-column: 5;
            grid-row: 1;
            border-top: 0;
        }

        .wol-card-problem .ws-field-label {
            display: none;
        }

        .wol-card-problem .ws-recent-order-meta {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            -webkit-line-clamp: initial;
        }

        .wol-card-footer .ws-row-time {
            grid-column: 6;
            grid-row: 1;
            white-space: nowrap;
        }

        .wol-card-footer .wol-card-chevron {
            grid-column: 7;
            grid-row: 1;
        }
    }

    @media (max-width: 599px) {
        .wol-page .ws-page-actions,
        .wol-page .ws-page-actions .ws-primary-action {
            width: 100%;
        }

        .wol-section-heading {
            align-items: flex-end;
        }

        .wol-card {
            min-height: 0;
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
    <div class="wol-page">
        <header class="ws-page-hero">
            <div class="ws-page-hero-copy">
                <h1 class="ws-page-display-title">Εργασίες</h1>
                <p class="ws-page-subtitle">Οι ενεργές εντολές του συνεργείου σε μία καθαρή προβολή.</p>
            </div>

            <div class="ws-page-actions">
                <a href="{{ route('workshop.work-orders.create') }}" class="ws-primary-action">
                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Νέα εντολή
                </a>
            </div>
        </header>

        <section aria-labelledby="wol-open-orders-title">
            <div class="wol-section-heading">
                <h2 id="wol-open-orders-title" class="ws-panel-title">Ανοιχτές εντολές</h2>
                <span class="ws-panel-meta">
                    {{ $workOrders->count() }} {{ $workOrders->count() === 1 ? 'εντολή' : 'εντολές' }}
                </span>
            </div>

            <div class="ws-panel wol-grid">
                @forelse($workOrders as $order)
                    <x-workshop.work-order-row :order="$order" variant="index" />
                @empty
                    <x-workshop.empty-state icon="clipboard" title="Δεν υπάρχουν ανοιχτές εντολές">
                        <a href="{{ route('workshop.work-orders.create') }}">Άνοιγμα νέας εντολής</a>
                    </x-workshop.empty-state>
                @endforelse
            </div>
        </section>
    </div>
@endsection
