<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#2f3747">
    <title>@yield('title', 'Συνεργείο')</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            /* ── Palette: softer dark slate ─────────────────────── */
            --bg:           #252c3a;
            --surface:      #303849;
            --surface-2:    #394457;
            --surface-3:    #414c60;
            --border:       rgba(151, 163, 184, 0.22);
            --border-soft:  rgba(151, 163, 184, 0.14);

            /* ── Accent — amber stays ───────────────────────────── */
            --accent:       #f59e0b;
            --accent-dim:   rgba(245, 158, 11, 0.16);
            --accent-glow:  rgba(245, 158, 11, 0.28);

            /* ── Text ───────────────────────────────────────────── */
            --text:         #f1f5f9;
            --text-muted:   #c3cede;
            --text-faint:   #94a3b8;

            /* ── Status colors — unchanged ──────────────────────── */
            --success:      #3fb950;
            --success-dim:  rgba(63, 185, 80, 0.13);
            --danger:       #f85149;
            --danger-dim:   rgba(248, 81, 73, 0.13);
            --info:         #58a6ff;
            --info-dim:     rgba(88, 166, 255, 0.13);

            /* ── Shape ──────────────────────────────────────────── */
            --radius:       14px;
            --radius-sm:    9px;
            --radius-xs:    6px;
            --header-h:     58px;
            --nav-h:        64px;

            /* ── Shadows ─────────────────────────────────────────── */
            --shadow-sm:    0 1px 2px rgba(15, 23, 42, 0.18);
            --shadow-md:    0 12px 28px rgba(15, 23, 42, 0.20);
            --highlight:    inset 0 1px 0 rgba(255,255,255,0.05);
        }

        html { -webkit-text-size-adjust: 100%; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui,
                         'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overscroll-behavior: none;
        }

        a { color: inherit; text-decoration: none; }

        /* ── Header ──────────────────────────────────────────────── */
        /* ── Header ──────────────────────────────────────────────── */
        .ws-header {
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--header-h);
            background: rgba(47, 55, 71, 0.96);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-soft);
            box-shadow: 0 1px 0 rgba(255,255,255,0.05), 0 10px 22px rgba(15,23,42,0.12);
            display: flex;
            align-items: center;
            padding: 0 1rem;
        }

        .ws-header-inner {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ws-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 700;
            font-size: 1.0625rem;
            letter-spacing: 0;
            color: var(--text);
        }

        .ws-brand-pip {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.14), 0 0 12px var(--accent-glow);
        }

        .ws-admin-btn {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-faint);
            padding: 0.35rem 0.75rem;
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-xs);
            transition: color 0.15s, border-color 0.15s, background 0.15s;
        }
        .ws-admin-btn:hover {
            color: var(--text-muted);
            border-color: var(--border);
            background: var(--surface-2);
        }
        .ws-admin-btn svg { width: 13px; height: 13px; }

        /* ── Main content ────────────────────────────────────────── */
        .ws-main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.375rem 1rem calc(var(--nav-h) + 1.75rem);
        }

        /* ── Section label ───────────────────────────────────────── */
        .ws-label {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-faint);
            margin-bottom: 0.625rem;
        }

        .ws-gap { height: 1.625rem; }
        .ws-gap-sm { height: 1rem; }

        /* ── Bottom nav ──────────────────────────────────────────── */
        .ws-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--nav-h);
            background: rgba(48, 56, 73, 0.98);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-top: 1px solid var(--border-soft);
            box-shadow: 0 -1px 0 rgba(255,255,255,0.05), 0 -10px 24px rgba(15,23,42,0.14);
            display: flex;
            align-items: stretch;
            z-index: 100;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }

        .ws-nav-items {
            display: flex;
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
        }

        .ws-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            color: var(--text-faint);
            font-size: 0.625rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            padding: 0.25rem 0;
            transition: color 0.15s;
            -webkit-tap-highlight-color: transparent;
        }
        .ws-nav-item svg { width: 22px; height: 22px; stroke-width: 1.75; }
        .ws-nav-item.ws-active { color: var(--accent); }
        .ws-nav-item:not(.ws-active):hover { color: var(--text-muted); }

        /* ── Desktop nav (inside header) ────────────────────────── */
        .ws-desktop-nav {
            display: none;
            align-items: center;
            gap: 0.25rem;
        }

        .ws-dnav-item {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-muted);
            padding: 0.375rem 0.875rem;
            border-radius: var(--radius-xs);
            transition: color 0.15s, background 0.15s;
            white-space: nowrap;
        }
        .ws-dnav-item:hover    { color: var(--text); background: var(--surface-2); }
        .ws-dnav-item.ws-active { color: var(--accent); background: var(--accent-dim); font-weight: 600; }

        /* ── Desktop breakpoint ──────────────────────────────────── */
        @media (min-width: 768px) {
            .ws-header { padding: 0 2rem; }

            .ws-main {
                padding: 2.25rem 2rem 3.5rem;
            }

            .ws-desktop-nav { display: flex; }

            .ws-bottom-nav  { display: none; }
        }

        @stack('styles')
    </style>
</head>
<body>

<header class="ws-header">
    <div class="ws-header-inner">
        <div class="ws-brand">
            <span class="ws-brand-pip"></span>
            @yield('header-title', 'Συνεργείο')
        </div>
        <nav class="ws-desktop-nav">
            <a href="/workshop"
               class="ws-dnav-item {{ request()->is('workshop') && !request()->is('workshop/*') ? 'ws-active' : '' }}">
                Αρχική
            </a>
            <a href="{{ route('workshop.work-orders.index') }}"
               class="ws-dnav-item {{ request()->is('workshop/work-orders*') ? 'ws-active' : '' }}">
                Εργασίες
            </a>
            <a href="{{ route('workshop.search') }}"
               class="ws-dnav-item {{ request()->is('workshop/search*') ? 'ws-active' : '' }}">
                Αναζήτηση
            </a>
            <a href="{{ route('workshop.appointments.index') }}"
               class="ws-dnav-item {{ request()->is('workshop/appointments*') ? 'ws-active' : '' }}">
                Ραντεβού
            </a>
        </nav>
        <a href="/admin" class="ws-admin-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
            </svg>
            Admin
        </a>
    </div>
</header>

<main class="ws-main">
    @yield('content')
</main>

<nav class="ws-bottom-nav">
    <div class="ws-nav-items">
        <a href="/workshop" class="ws-nav-item {{ request()->is('workshop') ? 'ws-active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            Αρχική
        </a>
        <a href="{{ route('workshop.work-orders.index') }}" class="ws-nav-item {{ request()->is('workshop/work-orders*') ? 'ws-active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
            </svg>
            Εργασίες
        </a>
        <a href="{{ route('workshop.search') }}" class="ws-nav-item {{ request()->is('workshop/search*') ? 'ws-active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607 0A7.5 7.5 0 0 1 5.196 15.803"/>
            </svg>
            Αναζήτηση
        </a>
        <a href="{{ route('workshop.appointments.index') }}" class="ws-nav-item {{ request()->is('workshop/appointments*') ? 'ws-active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
            </svg>
            Ραντεβού
        </a>
    </div>
</nav>

@stack('scripts')
</body>
</html>
