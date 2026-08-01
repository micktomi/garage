<aside
    id="ws-sidebar"
    class="ws-sidebar"
    x-bind:class="{ 'ws-sidebar--open': open }"
>
    {{-- Mobile only: the drawer dismiss control. Hidden on desktop. --}}
    <button
        type="button"
        class="ws-drawer-close"
        x-ref="drawerClose"
        x-on:click="closeDrawer()"
        aria-label="Κλείσιμο μενού"
    >
        <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>
        </svg>
    </button>

    <a
        href="{{ route('workshop.dashboard') }}"
        class="ws-sidebar-brand"
        aria-label="Συνεργείο — Αρχική"
    >
        <span class="ws-sidebar-brand-full">Συνεργείο</span>
        <span class="ws-sidebar-brand-compact" aria-hidden="true">Σ</span>
    </a>

    <nav class="ws-sidebar-nav" aria-label="Κύρια πλοήγηση" x-on:click="open = false">
        <div class="ws-sidebar-primary">
            <x-workshop.shell.nav-item
                :href="route('workshop.dashboard')"
                label="Αρχική"
                :active="request()->routeIs('workshop.dashboard')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 11.25 9-7.5 9 7.5v8.25a.75.75 0 0 1-.75.75h-5.25v-6h-6v6H3.75A.75.75 0 0 1 3 19.5v-8.25Z"/>
                    </svg>
                </x-slot:icon>
            </x-workshop.shell.nav-item>

            <x-workshop.shell.nav-item
                :href="route('workshop.work-orders.index')"
                label="Εργασίες"
                :active="request()->routeIs('workshop.work-orders.*')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6m-6 4.5h6m-6 4.5h3m-6.75 6h13.5A1.5 1.5 0 0 0 20.25 18.75v-15a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v15a1.5 1.5 0 0 0 1.5 1.5Z"/>
                    </svg>
                </x-slot:icon>
            </x-workshop.shell.nav-item>

            <x-workshop.shell.nav-item
                :href="route('workshop.customers.index')"
                label="Πελάτες"
                :active="request()->routeIs('workshop.customers.*')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z"/>
                    </svg>
                </x-slot:icon>
            </x-workshop.shell.nav-item>

            <x-workshop.shell.nav-item
                :href="route('workshop.search')"
                label="Οχήματα"
                :active="request()->routeIs('workshop.search', 'workshop.vehicles.*')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 17.25.75-6 1.5-3h9l1.5 3 .75 6M3.75 14.25h16.5M6.75 17.25v1.5m10.5-1.5v1.5M7.5 14.25h.008v.008H7.5v-.008Zm9 0h.008v.008H16.5v-.008Z"/>
                    </svg>
                </x-slot:icon>
            </x-workshop.shell.nav-item>

            <x-workshop.shell.nav-item
                :href="route('workshop.appointments.index')"
                label="Ραντεβού"
                :active="request()->routeIs('workshop.appointments.*')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5a1.5 1.5 0 0 0 1.5-1.5V6.75a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5V19.5a1.5 1.5 0 0 0 1.5 1.5Z"/>
                    </svg>
                </x-slot:icon>
            </x-workshop.shell.nav-item>
        </div>

        <div class="ws-sidebar-footer">
            <x-workshop.shell.nav-item
                :href="url('/admin')"
                label="Admin"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.75h-4.5a1.5 1.5 0 0 0-1.5 1.5v13.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-4.5M13.5 3.75h6.75v6.75m0-6.75-9 9"/>
                    </svg>
                </x-slot:icon>
            </x-workshop.shell.nav-item>
        </div>
    </nav>
</aside>
