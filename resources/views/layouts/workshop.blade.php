<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F7F7F5">
    <title>@yield('title', 'Συνεργείο')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <script>
        if (window.matchMedia('(max-width: 767px)').matches) {
            window.location.replace(@js(url('/admin')));
        }
    </script>
    <style>
        :root {
            /* Surfaces */
            --ws-page: #F7F7F5;
            --ws-card: #FFFFFF;
            --ws-sunken: #F3F4F6;

            /* Text */
            --ws-text: #18181B;
            --ws-text-muted: #6B7280;
            --ws-text-faint: #9CA3AF;

            /* Lines */
            --ws-border: #E5E7EB;
            --ws-border-hover: #D4D4D8;

            /* Sidebar */
            --ws-nav: #1C2733;
            --ws-nav-hover: #24313F;
            --ws-nav-active: #2B3A4A;
            --ws-nav-text: #93A4B5;
            --ws-nav-text-hi: #F1F5F9;
            --ws-nav-divider: #334456;

            /* The only accent. Darkened from #C2620E so white text meets 4.5:1. */
            --ws-primary: #BA5D0D;
            --ws-primary-fg: #FFFFFF;

            /* Work-order statuses */
            --ws-status-new-bg: #F1F1EF;
            --ws-status-new-fg: #54534F;
            --ws-status-progress-bg: #E7F0FB;
            --ws-status-progress-fg: #1B5296;
            --ws-status-awaiting-bg: #FBF0DC;
            --ws-status-awaiting-fg: #7A4A08;
            --ws-status-ready-bg: #E8F3E4;
            --ws-status-ready-fg: #33660F;
            --ws-status-completed-bg: #F1F1EF;
            --ws-status-completed-fg: #54534F;
            --ws-status-expired-bg: #FBEBEB;
            --ws-status-expired-fg: #992B2B;

            /* Compatibility aliases for the existing workshop detail forms. */
            --bg: var(--ws-page);
            --surface: var(--ws-card);
            --surface-2: var(--ws-sunken);
            --surface-3: var(--ws-sunken);
            --border: var(--ws-border);
            --border-soft: var(--ws-border);
            --accent: var(--ws-text);
            --accent-dim: var(--ws-sunken);
            --accent-glow: transparent;
            --text: var(--ws-text);
            --text-muted: var(--ws-text-muted);
            --text-faint: var(--ws-text-muted);
            --success: var(--ws-status-ready-fg);
            --success-dim: var(--ws-status-ready-bg);
            --danger: var(--ws-status-expired-fg);
            --danger-dim: var(--ws-status-expired-bg);
            --info: var(--ws-status-progress-fg);
            --info-dim: var(--ws-status-progress-bg);
            --radius: 12px;
            --radius-sm: 8px;
            --radius-xs: 8px;
            --header-h: 56px;
            --nav-h: 0px;
            --shadow-sm: 0 1px 2px rgb(24 24 27 / 5%);
            --shadow-md: 0 8px 24px rgb(24 24 27 / 8%);
            --highlight: none;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html {
            min-width: 768px;
            background: var(--ws-page);
            -webkit-text-size-adjust: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--ws-page);
            color: var(--ws-text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        :where(a, button, input, select, textarea):focus-visible {
            outline: 2px solid var(--ws-primary);
            outline-offset: 2px;
        }

        .ws-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .ws-shell {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
        }

        .ws-sidebar {
            position: sticky;
            top: 0;
            width: 80px;
            height: 100vh;
            padding: 16px 8px;
            flex: 0 0 80px;
            display: flex;
            flex-direction: column;
            background: var(--ws-nav);
            color: var(--ws-nav-text);
        }

        .ws-sidebar-brand {
            width: 100%;
            min-height: 48px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: var(--ws-nav-text-hi);
            font-size: 15px;
            font-weight: 500;
            line-height: 20px;
        }

        .ws-sidebar-brand:hover {
            background: var(--ws-nav-hover);
        }

        .ws-sidebar-brand-full {
            display: none;
        }

        .ws-sidebar-brand-compact {
            display: block;
        }

        .ws-sidebar-nav,
        .ws-sidebar-primary {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ws-sidebar-nav {
            min-height: 0;
            flex: 1;
        }

        .ws-sidebar-item {
            width: 100%;
            min-height: 56px;
            padding: 4px 2px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            border-radius: 8px;
            color: var(--ws-nav-text);
            font-size: 12px;
            font-weight: 400;
            line-height: 14px;
            text-align: center;
            transition: color 120ms ease, background-color 120ms ease;
        }

        .ws-sidebar-item:hover {
            color: var(--ws-nav-text-hi);
            background: var(--ws-nav-hover);
        }

        .ws-sidebar-item[aria-current="page"] {
            color: var(--ws-nav-text-hi);
            background: var(--ws-nav-active);
        }

        .ws-sidebar-icon {
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .ws-sidebar-icon svg {
            width: 20px;
            height: 20px;
            stroke-width: 1.8;
        }

        .ws-sidebar-label {
            display: block;
        }

        .ws-sidebar-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid var(--ws-nav-divider);
        }

        .ws-content {
            min-width: 0;
            flex: 1;
        }

        .ws-main {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 24px 32px;
        }

        .ws-page-heading {
            margin-bottom: 16px;
        }

        .ws-page-date {
            margin: 0 0 4px;
            color: var(--ws-text-muted);
            font-size: 13px;
        }

        .ws-page-title {
            margin: 0;
            color: var(--ws-text);
            font-size: 28px;
            font-weight: 500;
            line-height: 32px;
        }

        .ws-page-heading-row {
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .ws-dashboard-controls {
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: minmax(0, 2fr) auto;
            gap: 12px;
        }

        .ws-search-form {
            position: relative;
            min-width: 0;
        }

        .ws-search-submit {
            position: absolute;
            z-index: 1;
            top: 6px;
            left: 6px;
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--ws-text-muted);
            cursor: pointer;
            transition: color 120ms ease, background-color 120ms ease;
        }

        .ws-search-submit:hover {
            background: var(--ws-sunken);
            color: var(--ws-text);
        }

        .ws-search-icon {
            width: 18px;
            height: 18px;
            pointer-events: none;
        }

        .ws-search-input {
            width: 100%;
            height: 48px;
            padding: 0 16px 0 44px;
            border: 1px solid var(--ws-border);
            border-radius: 8px;
            background: var(--ws-card);
            color: var(--ws-text);
            font-size: 15px;
            transition: border-color 120ms ease;
        }

        .ws-search-input::placeholder {
            color: var(--ws-text-muted);
            opacity: 1;
        }

        .ws-search-input:hover {
            border-color: var(--ws-border-hover);
        }

        .ws-primary-action {
            min-width: 160px;
            min-height: 48px;
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: var(--ws-primary);
            color: var(--ws-primary-fg);
            font-size: 14px;
            font-weight: 500;
            transition: opacity 120ms ease;
        }

        .ws-primary-action:hover {
            opacity: .92;
        }

        .ws-primary-action svg {
            width: 18px;
            height: 18px;
            stroke-width: 2;
        }

        .ws-stat-grid {
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .ws-stat-card {
            min-height: 88px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 8px;
            border-radius: 8px;
            background: var(--ws-sunken);
        }

        .ws-stat-label {
            color: var(--ws-text-muted);
            font-size: 13px;
            font-weight: 500;
            line-height: 16px;
        }

        .ws-stat-value {
            color: var(--ws-text);
            font-size: 28px;
            font-weight: 500;
            line-height: 28px;
        }

        .ws-stat-value--danger {
            color: var(--ws-status-expired-fg);
        }

        .ws-dashboard-sections {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .ws-section-header {
            min-height: 36px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .ws-section-header h2 {
            margin: 0;
            color: var(--ws-text-muted);
            font-size: 13px;
            font-weight: 500;
        }

        .ws-section-header a {
            min-height: 36px;
            padding: 8px 0 8px 12px;
            display: inline-flex;
            align-items: center;
            color: var(--ws-text-muted);
            font-size: 13px;
            transition: color 120ms ease;
        }

        .ws-section-header a:hover {
            color: var(--ws-text);
        }

        .ws-list {
            border: 1px solid var(--ws-border);
            border-radius: 12px;
            background: var(--ws-card);
        }

        .ws-work-order-row,
        .ws-kteo-row {
            min-height: 68px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid var(--ws-border);
            transition: background-color 120ms ease;
        }

        .ws-list > :first-child {
            border-radius: 12px 12px 0 0;
        }

        .ws-list > :last-child {
            border-bottom: 0;
            border-radius: 0 0 12px 12px;
        }

        .ws-list > :only-child {
            border-radius: 12px;
        }

        .ws-work-order-row:hover {
            background: var(--ws-sunken);
        }

        .ws-plate {
            flex-shrink: 0;
            padding: 5px 9px;
            border: 1px solid var(--ws-border);
            border-radius: 8px;
            background: var(--ws-sunken);
            color: var(--ws-text);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 13px;
            font-weight: 400;
            line-height: 18px;
            white-space: nowrap;
        }

        .ws-row-body {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .ws-row-title,
        .ws-row-meta,
        .ws-kteo-deadline {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-row-title {
            color: var(--ws-text);
            font-size: 15px;
            font-weight: 500;
            line-height: 20px;
        }

        .ws-row-meta,
        .ws-kteo-deadline {
            color: var(--ws-text-muted);
            font-size: 13px;
            font-weight: 400;
            line-height: 18px;
        }

        .ws-work-order-aside {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .ws-status-badge {
            max-width: 220px;
            padding: 3px 9px;
            display: inline-flex;
            align-items: center;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            line-height: 18px;
            white-space: nowrap;
        }

        .ws-status-badge--new {
            background: var(--ws-status-new-bg);
            color: var(--ws-status-new-fg);
        }

        .ws-status-badge--in-progress {
            background: var(--ws-status-progress-bg);
            color: var(--ws-status-progress-fg);
        }

        .ws-status-badge--awaiting-parts {
            background: var(--ws-status-awaiting-bg);
            color: var(--ws-status-awaiting-fg);
        }

        .ws-status-badge--ready {
            background: var(--ws-status-ready-bg);
            color: var(--ws-status-ready-fg);
        }

        .ws-status-badge--completed {
            background: var(--ws-status-completed-bg);
            color: var(--ws-status-completed-fg);
        }

        .ws-row-time {
            color: var(--ws-text-muted);
            font-size: 12px;
            font-weight: 400;
            line-height: 16px;
            white-space: nowrap;
        }

        .ws-kteo-deadline--expired {
            color: var(--ws-status-expired-fg);
        }

        .ws-kteo-actions {
            flex: 0 0 auto;
            display: flex;
            gap: 8px;
        }

        .ws-icon-action {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--ws-border);
            border-radius: 8px;
            background: var(--ws-card);
            color: var(--ws-text-muted);
            transition: color 120ms ease, border-color 120ms ease, background-color 120ms ease;
        }

        .ws-icon-action:hover {
            border-color: var(--ws-border-hover);
            background: var(--ws-sunken);
            color: var(--ws-text);
        }

        .ws-icon-action svg {
            width: 18px;
            height: 18px;
            stroke-width: 1.8;
        }

        .ws-empty-state {
            min-height: 76px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .ws-empty-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--ws-sunken);
            color: var(--ws-text-muted);
        }

        .ws-empty-icon svg {
            width: 20px;
            height: 20px;
            stroke-width: 1.6;
        }

        .ws-empty-copy {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 4px 8px;
        }

        .ws-empty-title {
            color: var(--ws-text-muted);
            font-size: 13px;
        }

        .ws-empty-action a {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            color: var(--ws-text);
            font-size: 13px;
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* Shared helpers retained for the existing workshop forms. */
        .ws-label {
            margin-bottom: 8px;
            color: var(--ws-text-muted);
            font-size: 13px;
            font-weight: 500;
        }

        .ws-gap {
            height: 24px;
        }

        .ws-gap-sm {
            height: 16px;
        }

        @media (min-width: 1280px) {
            .ws-sidebar {
                width: 240px;
                padding: 16px;
                flex-basis: 240px;
            }

            .ws-sidebar-brand {
                padding: 0 12px;
                justify-content: flex-start;
            }

            .ws-sidebar-brand-full {
                display: block;
            }

            .ws-sidebar-brand-compact {
                display: none;
            }

            .ws-sidebar-item {
                min-height: 44px;
                padding: 10px 12px;
                flex-direction: row;
                justify-content: flex-start;
                gap: 12px;
                font-size: 13px;
                line-height: 18px;
                text-align: left;
            }
        }

        @media (max-width: 1023px) {
            .ws-main {
                padding-left: 16px;
                padding-right: 16px;
            }

            .ws-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }

    </style>
    @stack('styles')
    @vite(['resources/js/app.js'])
</head>
<body>
    <div class="ws-shell">
        <x-workshop.shell.sidebar />

        <div class="ws-content">
            <main class="ws-main">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
