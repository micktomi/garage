@if($attributes->has('dashboard'))
    <a
        href="{{ route('workshop.work-orders.show', $order) }}"
        class="ws-recent-order"
        data-status="{{ $order->status->value }}"
    >
        <x-workshop.plate :value="$plate" size="sm" />

        <span class="ws-recent-order-body">
            <span class="ws-recent-order-customer" @if($customerTooltip) title="{{ $customerTooltip }}" @endif>{{ $customerName }}</span>
            <span class="ws-recent-order-meta" @if($metaTooltip) title="{{ $metaTooltip }}" @endif>{{ $meta }}</span>
        </span>

        <span class="ws-recent-order-aside">
            <span class="ws-recent-order-number">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>

            <span class="ws-recent-order-status">
                <x-workshop.status-badge :status="$order->status" />

                {{-- Explains why an open order is missing from the strip above. --}}
                @if($outOfShop)
                    <span class="ws-shop-flag">Εκτός συνεργείου</span>
                @endif
            </span>

            <time class="ws-recent-order-time" @if($createdDateTime) datetime="{{ $createdDateTime }}" @endif>{{ $dashboardDateLabel }}</time>

            <svg class="ws-recent-order-chevron" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
            </svg>
        </span>
    </a>
@elseif($attributes->get('variant') === 'index')
    <a
        href="{{ route('workshop.work-orders.show', $order) }}"
        class="ws-panel ws-clickable-card wol-card"
        aria-label="Εντολή #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}, κατάσταση {{ $order->status->label() }}, πελάτης {{ $customerName }}, όχημα {{ $vehicleName }}, πινακίδα {{ $plate }}, πρόβλημα {{ $problem }}, άνοιγμα {{ $dashboardDateLabel }}"
    >
        <span class="wol-card-top">
            <span class="ws-recent-order-number">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
            <x-workshop.status-badge :status="$order->status" />
        </span>

        <span class="wol-card-person">
            <span class="ws-recent-order-customer">{{ $customerName }}</span>
            <span class="ws-recent-order-meta">{{ $vehicleName }}</span>
        </span>

        <span class="wol-card-meta">
            <x-workshop.plate :value="$plate" />
        </span>

        <span class="wol-card-problem">
            <span class="ws-field-label">Πρόβλημα</span>
            <span class="ws-recent-order-meta">{{ $problem }}</span>
        </span>

        <span class="wol-card-footer">
            <time class="ws-row-time" @if($createdDateTime) datetime="{{ $createdDateTime }}" @endif>{{ $dashboardDateLabel }}</time>
            <svg class="wol-card-chevron" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
            </svg>
        </span>
    </a>
@else
    <a href="{{ route('workshop.work-orders.show', $order) }}" class="ws-work-order-row">
        <x-workshop.plate :value="$plate" />

        <span class="ws-row-body">
            <span class="ws-row-title" @if($customerTooltip) title="{{ $customerTooltip }}" @endif>{{ $customerName }}</span>
            <span class="ws-row-meta" @if($metaTooltip) title="{{ $metaTooltip }}" @endif>{{ $meta }}</span>
        </span>

        <span class="ws-work-order-aside">
            <x-workshop.status-badge :status="$order->status" />
            <time class="ws-row-time" @if($createdDateTime) datetime="{{ $createdDateTime }}" @endif>{{ $createdLabel }}</time>
        </span>
    </a>
@endif
