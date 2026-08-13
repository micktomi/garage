<div {{ $attributes->class(['ws-stat-card', 'ws-stat-card--alert' => $tone === 'danger']) }}>
    <span class="ws-stat-icon" aria-hidden="true">{{ $icon ?? '' }}</span>

    <span class="ws-stat-content">
        <span class="ws-stat-label">{{ $label }}</span>
        <span class="ws-stat-row">
            <span class="ws-stat-value {{ $tone === 'danger' ? 'ws-stat-value--danger' : '' }}">{{ $value }}</span>
        </span>
    </span>
</div>
