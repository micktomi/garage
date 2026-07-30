<div class="ws-kteo-row">
    <x-workshop.plate :value="$plate" />

    <span class="ws-row-body">
        <span class="ws-row-title" title="{{ $customerName }}">{{ $customerName }}</span>
        <time
            class="ws-kteo-deadline {{ $expired ? 'ws-kteo-deadline--expired' : '' }}"
            @if($deadlineDateTime) datetime="{{ $deadlineDateTime }}" @endif
        >{{ $deadlineLabel }}</time>
    </span>

    @if($phoneHref)
        <span class="ws-kteo-actions">
            <a href="tel:{{ $phoneHref }}" class="ws-icon-action" aria-label="Κλήση {{ $customerName }}">
                <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5A2.25 2.25 0 0 0 21 19.5v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102A1.125 1.125 0 0 0 5.872 2.25H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
            </a>
            <a href="sms:{{ $phoneHref }}" class="ws-icon-action" aria-label="SMS προς {{ $customerName }}">
                <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25S21 7.444 21 12Z" />
                </svg>
            </a>
        </span>
    @endif
</div>
