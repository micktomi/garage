<aside
    id="ws-sidebar"
    class="ws-sidebar"
    x-bind:class="{ 'ws-sidebar--open': open }"
>
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
        aria-label="Garage Manager — Πίνακας Ελέγχου"
    >
        <span class="ws-sidebar-brand-mark" aria-hidden="true">Σ</span>
        <span>
            <span class="ws-sidebar-brand-full">Garage Manager</span>
            <span class="ws-sidebar-brand-sub" aria-hidden="true">Συνεργείο</span>
        </span>
        <span class="ws-sidebar-brand-compact" aria-hidden="true">GM</span>
    </a>

    <nav class="ws-sidebar-nav" aria-label="Κύρια πλοήγηση" x-on:click="open = false">
        <div class="ws-sidebar-primary">
            <div class="ws-sidebar-group-label" aria-hidden="true">Καθημερινά</div>

            <x-workshop.shell.nav-item
                :href="route('workshop.dashboard')"
                label="Πίνακας Ελέγχου"
                :active="request()->routeIs('workshop.dashboard')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5h6v6h-6v-6Zm9 0h6v6h-6v-6Zm-9 9h6v6h-6v-6Zm9 0h6v6h-6v-6Z"/>
                    </svg>
                </x-slot:icon>
            </x-workshop.shell.nav-item>

            <x-workshop.shell.nav-item
                :href="route('workshop.work-orders.index')"
                label="Εντολές Εργασίας"
                :active="request()->routeIs('workshop.work-orders.*')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h16M6 17l1.5-5.5A2 2 0 0 1 9.4 10h5.2a2 2 0 0 1 1.9 1.5L18 17M4 17v2.5M20 17v2.5"/>
                        <circle cx="7.5" cy="17" r="1"/>
                        <circle cx="16.5" cy="17" r="1"/>
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

            <div class="ws-sidebar-group-label" aria-hidden="true">Αρχείο</div>

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
                :href="route('workshop.registration-scan.show')"
                label="Σάρωση Άδειας"
                :active="request()->routeIs('workshop.registration-scan.*')"
            >
                <x-slot:icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5V5.25a.75.75 0 0 1 .75-.75H7.5m9 0h2.25a.75.75 0 0 1 .75.75V7.5m0 9v2.25a.75.75 0 0 1-.75.75H16.5m-9 0H5.25a.75.75 0 0 1-.75-.75V16.5M8.25 9.75h7.5v4.5h-7.5v-4.5Z"/>
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

            <div class="ws-sidebar-identity">
                <span class="ws-sidebar-identity-avatar" aria-hidden="true">{{ mb_substr(auth()->user()?->name ?: 'Δ', 0, 1) }}</span>
                <span class="ws-sidebar-identity-name">{{ auth()->user()?->name ?: 'Διαχειριστής' }}</span>
            </div>
        </div>
    </nav>
</aside>
