@props(['title'])

<section class="woc-form-section">
    <h2 class="woc-form-section-title">{{ $title }}</h2>
    <div class="woc-form-section-body">
        {{ $slot }}
    </div>
</section>
