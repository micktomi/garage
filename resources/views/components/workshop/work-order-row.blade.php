<a href="{{ route('workshop.work-orders.show', $order) }}" class="ws-work-order-row">
    <x-workshop.plate :value="$plate" />

    <span class="ws-row-body">
        <span class="ws-row-title" title="{{ $customerName }}">{{ $customerName }}</span>
        <span class="ws-row-meta" title="{{ $meta }}">{{ $meta }}</span>
    </span>

    <span class="ws-work-order-aside">
        <x-workshop.status-badge :status="$order->status" />
        <time class="ws-row-time" @if($createdDateTime) datetime="{{ $createdDateTime }}" @endif>{{ $createdLabel }}</time>
    </span>

    @if($attributes->has('dashboard') || $attributes->get('variant') === 'index')
        <svg class="ws-work-order-chevron" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
        </svg>
    @endif
</a>
