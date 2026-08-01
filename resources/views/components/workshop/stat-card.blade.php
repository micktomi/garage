<div class="ws-stat-card ws-stat-card--{{ $accent }}">
    <span class="ws-stat-icon" aria-hidden="true">
        @if($icon === 'calendar')
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5a1.5 1.5 0 0 0 1.5-1.5V6.75a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5V19.5a1.5 1.5 0 0 0 1.5 1.5Z"/>
            </svg>
        @elseif($icon === 'clock')
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25m5-2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
        @elseif($icon === 'parts')
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5m-10.5 4.5h10.5m-10.5 4.5h6.75M5.25 3.75h13.5a1.5 1.5 0 0 1 1.5 1.5v13.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z"/>
            </svg>
        @else
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6m-6 4.5h6m-6 4.5h3m-6.75 6h13.5A1.5 1.5 0 0 0 20.25 18.75v-15a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v15a1.5 1.5 0 0 0 1.5 1.5Z"/>
            </svg>
        @endif
    </span>

    <span class="ws-stat-copy">
        <span class="ws-stat-label">{{ $label }}</span>
        <span class="ws-stat-value {{ $tone === 'danger' ? 'ws-stat-value--danger' : '' }}">{{ $value }}</span>
    </span>
</div>
