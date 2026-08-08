<a
    href="{{ route('workshop.work-orders.show', $order) }}"
    class="ws-bay"
    data-status="{{ $order->status->value }}"
    aria-label="Εντολή #{{ $orderNumber }}, {{ $plate }}, {{ $vehicleName }}, {{ $order->status->label() }}, {{ $elapsedLabel }}"
>
    <span class="ws-bay-number" aria-hidden="true">{{ $orderNumber }}</span>

    <x-workshop.plate :value="$plate" />

    <span class="ws-bay-vehicle">{{ $vehicleName }}</span>
    <span class="ws-bay-job" @if($jobTooltip) title="{{ $jobTooltip }}" @endif>{{ $jobLine }}</span>

    <span class="ws-bay-rail" aria-hidden="true">
        @for($step = 1; $step <= $stages; $step++)
            <span class="ws-bay-rail-step {{ $step <= $stage ? 'is-filled' : '' }}"></span>
        @endfor
    </span>

    <span class="ws-bay-foot">
        <span class="ws-bay-elapsed">{{ $elapsedLabel }}</span>
        <x-workshop.status-badge :status="$order->status" />
    </span>
</a>
