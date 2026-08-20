<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0F6E56">
    <title>@yield('title', 'Συνεργείο')</title>
    @vite(['resources/css/workshop.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body
    x-data="{
        open: false,
        drawerTrigger: null,
        openDrawer(trigger = null) {
            this.drawerTrigger = trigger;
            this.open = true;
            this.$nextTick(() => this.$refs.drawerClose?.focus());
        },
        closeDrawer() {
            if (! this.open) {
                return;
            }

            this.open = false;
            const returnTarget = this.drawerTrigger;
            this.drawerTrigger = null;
            this.$nextTick(() => returnTarget?.focus());
        },
    }"
    x-bind:class="{ 'ws-drawer-open': open }"
    x-effect="$refs.mobileHeader.inert = open; $refs.contentRegion.inert = open; $refs.mobileBottomNav.inert = open"
    x-on:keydown.escape.window="closeDrawer()"
    x-on:resize.window="if (window.innerWidth >= 768) open = false"
>
    <header class="ws-mobile-header" x-ref="mobileHeader">
        @hasSection('header-back')
            <a href="@yield('header-back')" class="ws-mobile-header-action ws-mobile-back-action" aria-label="Πίσω" title="Πίσω">
                <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 19.5-7.5-7.5 7.5-7.5"/>
                </svg>
            </a>
        @endif

        <div class="ws-mobile-context">
            <span class="ws-mobile-context-title">@yield('header-title', 'Πίνακας Ελέγχου')</span>
            @hasSection('header-subtitle')
                <span class="ws-mobile-context-subtitle">@yield('header-subtitle')</span>
            @endif
        </div>

        {{-- The dashboard already carries its own, more prominent search
             field below the header — the icon here would only duplicate it.
             Every other /workshop page has no inline search field of its
             own, so it keeps this as its one entry point to search. --}}
        @unless(request()->routeIs('workshop.dashboard'))
            <a href="{{ route('workshop.search') }}" class="ws-mobile-header-action" aria-label="Αναζήτηση οχήματος" title="Αναζήτηση οχήματος">
                <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="10.75" cy="10.75" r="6.75" />
                    <path stroke-linecap="round" d="m15.75 15.75 4.5 4.5" />
                </svg>
            </a>
        @endunless
    </header>

    <div class="ws-shell">
        <div
            class="ws-drawer-backdrop"
            x-cloak
            x-show="open"
            x-transition.opacity.duration.200ms
            x-on:click="closeDrawer()"
            aria-hidden="true"
        ></div>

        <x-workshop.shell.sidebar />

        <div class="ws-content" x-ref="contentRegion">
            <main class="ws-main">
                @yield('content')
            </main>
        </div>
    </div>

    <x-workshop.shell.mobile-bottom-nav />

    @stack('scripts')
</body>
</html>
