@props(['title', 'href' => null, 'link' => null])

<div class="ws-section-header">
    <h2>
        <span class="ws-section-label-desktop">{{ $title }}</span>
        @if($attributes->has('mobile-title'))
            <span class="ws-section-label-mobile">{{ $attributes->get('mobile-title') }}</span>
        @endif
    </h2>
    @if($href && $link)
        <a href="{{ $href }}">
            <span class="ws-section-label-desktop">{{ $link }}</span>
            @if($attributes->has('mobile-link'))
                <span class="ws-section-label-mobile">{{ $attributes->get('mobile-link') }}</span>
            @endif
            <span aria-hidden="true">→</span>
        </a>
    @endif
</div>
