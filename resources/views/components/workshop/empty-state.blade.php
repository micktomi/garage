<div class="ws-empty-state">
    <span class="ws-empty-icon" aria-hidden="true">
        @if($icon === 'clipboard')
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9.75 4.5h4.5A2.25 2.25 0 0 1 16.5 6.75v.75h.75A2.25 2.25 0 0 1 19.5 9.75v9A2.25 2.25 0 0 1 17.25 21h-10.5A2.25 2.25 0 0 1 4.5 18.75v-9A2.25 2.25 0 0 1 6.75 7.5h.75v-.75A2.25 2.25 0 0 1 9.75 4.5Z" />
            </svg>
        @else
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 18.75 6H5.25a1.5 1.5 0 0 0-1.5 1.5v12A1.5 1.5 0 0 0 5.25 21Z" />
            </svg>
        @endif
    </span>
    <span class="ws-empty-copy">
        <span class="ws-empty-title">{{ $title }}</span>
        @if(trim((string) $slot) !== '')
            <span class="ws-empty-action">{{ $slot }}</span>
        @endif
    </span>
</div>
