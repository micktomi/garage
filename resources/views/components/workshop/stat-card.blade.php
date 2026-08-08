<div class="ws-stat-card {{ $tone === 'danger' ? 'ws-stat-card--alert' : '' }}">
    <span class="ws-stat-label">{{ $label }}</span>
    <span class="ws-stat-row">
        <span class="ws-stat-value {{ $tone === 'danger' ? 'ws-stat-value--danger' : '' }}">{{ $value }}</span>
    </span>
</div>
