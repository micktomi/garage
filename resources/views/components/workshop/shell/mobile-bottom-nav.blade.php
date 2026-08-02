<nav class="ws-mobile-bottom-nav" x-ref="mobileBottomNav" aria-label="Κύρια πλοήγηση κινητού">
    <a
        href="{{ route('workshop.dashboard') }}"
        class="ws-mobile-bottom-item"
        @if(request()->routeIs('workshop.dashboard')) aria-current="page" @endif
    >
        <span class="ws-mobile-bottom-icon" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 10.5 12 3l8.25 7.5v8.25a1.5 1.5 0 0 1-1.5 1.5h-13.5a1.5 1.5 0 0 1-1.5-1.5V10.5Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20.25v-6h6v6"/>
            </svg>
        </span>
        <span class="ws-mobile-bottom-label">Αρχική</span>
    </a>

    <a
        href="{{ route('workshop.work-orders.index') }}"
        class="ws-mobile-bottom-item"
        @if(request()->routeIs('workshop.work-orders.index', 'workshop.work-orders.show')) aria-current="page" @endif
    >
        <span class="ws-mobile-bottom-icon" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6m-6 4.5h6m-6 4.5h3m-6.75 6h13.5A1.5 1.5 0 0 0 20.25 18.75v-15a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v15a1.5 1.5 0 0 0 1.5 1.5Z"/>
            </svg>
        </span>
        <span class="ws-mobile-bottom-label">Εργασίες</span>
    </a>

    <a
        href="{{ route('workshop.work-orders.create') }}"
        class="ws-mobile-bottom-item ws-mobile-bottom-item--create"
        aria-label="Νέα εντολή"
        title="Νέα εντολή"
        @if(request()->routeIs('workshop.work-orders.create')) aria-current="page" @endif
    >
        <span class="ws-mobile-bottom-icon" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v13.5M18.75 12H5.25"/>
            </svg>
        </span>
    </a>

    <a
        href="{{ route('workshop.appointments.index') }}"
        class="ws-mobile-bottom-item"
        @if(request()->routeIs('workshop.appointments.*')) aria-current="page" @endif
    >
        <span class="ws-mobile-bottom-icon" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5a1.5 1.5 0 0 0 1.5-1.5V6.75a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v12A1.5 1.5 0 0 0 5.25 21Z"/>
            </svg>
        </span>
        <span class="ws-mobile-bottom-label">Ραντεβού</span>
    </a>

    <button
        type="button"
        class="ws-mobile-bottom-item"
        aria-label="Περισσότερα"
        aria-controls="ws-sidebar"
        aria-expanded="false"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        x-on:click="openDrawer($el)"
        @if(request()->routeIs('workshop.customers.*', 'workshop.search', 'workshop.vehicles.*', 'workshop.kteo')) aria-current="page" @endif
    >
        <span class="ws-mobile-bottom-icon" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 12h.008v.008H5.25V12Zm6.75 0h.008v.008H12V12Zm6.75 0h.008v.008h-.008V12Z"/>
            </svg>
        </span>
        <span class="ws-mobile-bottom-label">Περισσότερα</span>
    </button>
</nav>
