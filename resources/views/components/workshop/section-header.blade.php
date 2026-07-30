<div class="ws-section-header">
    <h2>{{ $title }}</h2>
    @if($href && $link)
        <a href="{{ $href }}">{{ $link }} <span aria-hidden="true">→</span></a>
    @endif
</div>
