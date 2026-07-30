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
</a>
