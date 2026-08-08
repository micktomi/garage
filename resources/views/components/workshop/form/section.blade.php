@props(['title', 'step' => null])

<section class="woc-form-section">
    <div class="woc-form-section-head">
        @if($step)
            <span class="woc-step" aria-hidden="true">{{ $step }}</span>
        @endif
        <h2 class="woc-form-section-title">{{ $title }}</h2>
    </div>
    <div class="woc-form-section-body">
        {{ $slot }}
    </div>
</section>
