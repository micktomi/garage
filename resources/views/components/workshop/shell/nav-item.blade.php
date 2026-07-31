@props([
    'href',
    'label',
    'active' => false,
])

<a
    href="{{ $href }}"
    class="ws-sidebar-item"
    @if($active) aria-current="page" @endif
>
    <span class="ws-sidebar-icon" aria-hidden="true">
        {{ $icon }}
    </span>
    <span class="ws-sidebar-label">{{ $label }}</span>
</a>
