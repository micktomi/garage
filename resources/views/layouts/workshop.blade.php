<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#E9EDE8">
    <title>@yield('title', 'Συνεργείο')</title>
    <style>
        :root {
            /* Surfaces */
            --ws-page: #E9EDE8;
            --ws-card: #FFFFFF;
            --ws-sunken: #F6F9F5;

            /* Text */
            --ws-text: #0D2A2F;
            --ws-text-muted: #4A6360;
            --ws-text-faint: #7B918D;

            /* Lines */
            --ws-border: #DBE1DA;
            --ws-border-hover: #C4CFC5;

            /* Sidebar */
            --ws-nav: #0D2A2F;
            --ws-nav-hover: #17383D;
            --ws-nav-active: #1D4046;
            --ws-nav-accent: #FF5A1F;
            --ws-nav-text: #B9CBC7;
            --ws-nav-text-hi: #FFFFFF;
            --ws-nav-divider: #244A4F;

            /* Donor primary action: the deep ink button, not a coloured accent. */
            --ws-primary: #0D2A2F;
            --ws-primary-hover: #14383E;
            --ws-primary-fg: #FFFFFF;

            /* Signal is reserved for attention, never for ordinary actions. */
            --ws-signal: #FF5A1F;

            /* Greek registration plate band. */
            --ws-plate-band: #0B3AA8;
            --ws-plate-band-fg: #FFD200;

            /* Explicit Greek-capable system stacks — no webfont requests. */
            --ws-font-sans: ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans", Arial, sans-serif;
            --ws-font-display: "Roboto Condensed", "Liberation Sans Narrow", "Arial Narrow", "Noto Sans", ui-sans-serif, system-ui, sans-serif;
            --ws-font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Noto Sans Mono", monospace;
            --ws-font-serif: Georgia, "Times New Roman", "Noto Serif", serif;

            /* Work-order statuses */
            --ws-status-new-bg: #E4F2EC;
            --ws-status-new-fg: #1E7A5C;
            --ws-status-new-border: #B8DCCF;
            --ws-status-progress-bg: #E6EDFB;
            --ws-status-progress-fg: #2C5CC5;
            --ws-status-progress-border: #BCCEF0;
            --ws-status-awaiting-bg: #FBF0DA;
            --ws-status-awaiting-fg: #7A4A08;
            --ws-status-awaiting-border: #E8CA8E;
            --ws-status-ready-bg: #D9EDE2;
            --ws-status-ready-fg: #135C44;
            --ws-status-ready-border: #A8D2BC;
            --ws-status-completed-bg: #EDF0EC;
            --ws-status-completed-fg: #4A6360;
            --ws-status-completed-border: #D4DBD5;
            --ws-status-expired-bg: #FFF1EA;
            --ws-status-expired-fg: #B23A0F;
            --ws-status-expired-border: #F2C2AC;

            /* Dashboard signals */
            --ws-stat-appointments-bg: #EDF8FC;
            --ws-stat-appointments-fg: #08779B;
            --ws-stat-appointments-border: #B7DFEB;
            --ws-divider-soft: #E8ECE7;

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
            --radius: 10px;
            --radius-sm: 8px;
            --radius-xs: 8px;
            --header-h: 56px;
            --nav-h: 0px;
            --shadow-sm: 0 1px 2px rgb(13 42 47 / 5%), 0 10px 28px -18px rgb(13 42 47 / 30%);
            --shadow-md: 0 2px 4px rgb(13 42 47 / 6%), 0 18px 40px -22px rgb(13 42 47 / 40%);
            --highlight: none;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html {
            background: var(--ws-page);
            -webkit-text-size-adjust: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            /* Donor workshop paper: fine diagonal hatch plus a soft top-right lift. */
            background-color: var(--ws-page);
            background-image:
                repeating-linear-gradient(135deg, rgb(13 42 47 / 1.5%) 0 1px, transparent 1px 9px),
                radial-gradient(1100px 520px at 78% -8%, rgb(255 255 255 / 90%), transparent 70%);
            background-attachment: fixed;
            color: var(--ws-text);
            font-family: var(--ws-font-sans);
            font-size: 15px;
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

        [x-cloak] {
            display: none !important;
        }

        /* Mobile-only shell pieces. Hidden on desktop, so the desktop layout is untouched. */
        .ws-mobile-header,
        .ws-mobile-bottom-nav,
        .ws-drawer-backdrop,
        .ws-drawer-close {
            display: none;
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
            width: 224px;
            height: 100vh;
            padding: 18px 12px;
            flex: 0 0 224px;
            display: flex;
            flex-direction: column;
            background: var(--ws-nav);
            color: var(--ws-nav-text);
            border-right: 1px solid var(--ws-border);
        }

        .ws-sidebar-brand {
            width: 100%;
            min-height: 48px;
            margin-bottom: 22px;
            padding: 0 8px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 11px;
            border-radius: 8px;
            color: var(--ws-nav-text-hi);
            font-family: var(--ws-font-sans);
            font-size: 14px;
            font-weight: 700;
            line-height: 20px;
            letter-spacing: -0.01em;
        }

        .ws-sidebar-brand:hover {
            background: var(--ws-nav-hover);
        }

        .ws-sidebar-brand-mark {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--ws-nav-accent);
            color: #FFFFFF;
            font-family: var(--ws-font-display);
            font-size: 17px;
            font-weight: 700;
            line-height: 1;
        }

        .ws-sidebar-brand-full {
            display: block;
            font-family: var(--ws-font-display);
            font-size: 17px;
            font-weight: 700;
            line-height: 1.15;
        }

        .ws-sidebar-brand-sub {
            display: block;
            color: var(--ws-nav-text);
            font-family: var(--ws-font-sans);
            font-size: 11px;
            font-weight: 500;
            line-height: 15px;
            letter-spacing: .14em;
            text-transform: uppercase;
            opacity: .72;
        }

        .ws-sidebar-brand-compact {
            display: none;
        }

        .ws-sidebar-nav,
        .ws-sidebar-primary {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .ws-sidebar-group-label {
            padding: 0 10px 8px;
            color: var(--ws-nav-text);
            font-family: var(--ws-font-sans);
            font-size: 10px;
            font-weight: 600;
            line-height: 14px;
            letter-spacing: .16em;
            text-transform: uppercase;
            opacity: .62;
        }

        .ws-sidebar-group-label + .ws-sidebar-group-label,
        .ws-sidebar-primary > .ws-sidebar-item + .ws-sidebar-group-label {
            padding-top: 18px;
        }

        .ws-sidebar-nav {
            min-height: 0;
            flex: 1;
        }

        .ws-sidebar-item {
            width: 100%;
            min-height: 44px;
            padding: 10px 10px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 11px;
            border-radius: 8px;
            color: var(--ws-nav-text);
            font-family: var(--ws-font-sans);
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            text-align: left;
            transition: color 120ms ease, background-color 120ms ease;
        }

        .ws-sidebar-item:hover {
            color: var(--ws-nav-text-hi);
            background: var(--ws-nav-hover);
        }

        .ws-sidebar-item[aria-current="page"] {
            color: var(--ws-primary-fg);
            background: var(--ws-nav-active);
            box-shadow: inset 3px 0 0 var(--ws-nav-accent);
        }

        .ws-sidebar-icon {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--ws-nav-text);
            opacity: .85;
        }

        .ws-sidebar-item:hover .ws-sidebar-icon {
            color: var(--ws-nav-text-hi);
            opacity: 1;
        }

        .ws-sidebar-item[aria-current="page"] .ws-sidebar-icon {
            color: var(--ws-nav-accent);
            opacity: 1;
        }

        .ws-sidebar-icon svg {
            width: 18px;
            height: 18px;
            stroke-width: 1.8;
        }

        .ws-sidebar-label {
            display: block;
        }

        .ws-sidebar-footer {
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid var(--ws-nav-divider);
        }

        .ws-sidebar-identity {
            margin-top: 6px;
            padding: 10px 10px 2px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--ws-nav-text);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            line-height: 18px;
        }

        .ws-sidebar-identity-avatar {
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: var(--ws-nav-active);
            color: var(--ws-nav-text-hi);
            font-size: 12px;
            font-weight: 600;
        }

        .ws-sidebar-identity-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-content {
            min-width: 0;
            flex: 1;
        }

        .ws-main {
            width: 100%;
            max-width: 1560px;
            margin: 0;
            padding: 26px 30px 60px;
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
            height: 52px;
            padding: 0 16px 0 44px;
            border: 1px solid var(--ws-border);
            border-radius: 10px;
            background: var(--ws-card);
            color: var(--ws-text);
            font-size: 15px;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .ws-search-input::placeholder {
            color: var(--ws-text-faint);
            opacity: 1;
        }

        .ws-search-input:hover {
            border-color: var(--ws-border-hover);
        }

        .ws-search-input:focus {
            border-color: var(--ws-text);
            outline: none;
            box-shadow: 0 0 0 3px rgb(13 42 47 / 10%);
        }

        .ws-primary-action {
            min-width: 160px;
            min-height: 52px;
            padding: 8px 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid transparent;
            border-radius: 10px;
            background: var(--ws-primary);
            color: var(--ws-primary-fg);
            font-size: 15px;
            font-weight: 500;
            transition: background-color 150ms ease, transform 100ms ease;
        }

        .ws-primary-action:hover {
            background: var(--ws-primary-hover);
        }

        .ws-primary-action:active {
            transform: translateY(1px);
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

        .ws-section-label-mobile {
            display: none;
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
            box-shadow: var(--shadow-sm);
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

        .ws-work-order-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: stretch;
            gap: 12px;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        .ws-work-order-grid .ws-work-order-row {
            min-width: 0;
            min-height: 84px;
            height: 100%;
            padding: 14px;
            gap: 12px;
            border: 1px solid var(--ws-border);
            border-radius: 12px;
            background: var(--ws-card);
            box-shadow: var(--shadow-sm);
        }

        .ws-work-order-grid .ws-work-order-row:hover {
            border-color: var(--ws-border-hover);
            background: var(--ws-sunken);
        }

        .ws-work-order-grid .ws-work-order-aside {
            max-width: 45%;
            margin-left: auto;
        }

        .ws-work-order-grid .ws-status-badge {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ws-work-order-chevron {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            color: var(--ws-text-faint);
            stroke-width: 1.8;
            transition: color 120ms ease, transform 120ms ease;
        }

        .ws-work-order-grid .ws-work-order-row:hover .ws-work-order-chevron {
            color: var(--ws-text-muted);
            transform: translateX(2px);
        }

        .ws-work-order-grid > .ws-empty-state {
            grid-column: 1 / -1;
            border: 1px solid var(--ws-border);
            border-radius: 12px;
            background: var(--ws-card);
        }

        /* Greek registration plate — the donor's EU band plus the number field. */
        .ws-plate {
            max-width: 100%;
            flex-shrink: 0;
            display: inline-flex;
            align-items: stretch;
            overflow: hidden;
            border: 1.5px solid var(--ws-text);
            border-radius: 5px;
            background: var(--ws-card);
            color: var(--ws-text);
            font-family: var(--ws-font-mono);
            vertical-align: middle;
        }

        .ws-plate-band {
            width: 15px;
            flex: 0 0 15px;
            padding-top: 4px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            background: var(--ws-plate-band);
            color: var(--ws-plate-band-fg);
            font-size: 8px;
            font-weight: 600;
            line-height: 1;
        }

        .ws-plate-number {
            min-width: 0;
            padding: 5px 9px;
            overflow: hidden;
            font-size: 14px;
            font-weight: 600;
            line-height: 18px;
            letter-spacing: .06em;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-plate--sm .ws-plate-band {
            width: 12px;
            flex: 0 0 12px;
            padding-top: 3px;
            font-size: 7px;
        }

        .ws-plate--sm .ws-plate-number {
            padding: 4px 7px;
            font-size: 12.5px;
            line-height: 16px;
        }

        .ws-plate--lg .ws-plate-band {
            width: 19px;
            flex: 0 0 19px;
            padding-top: 6px;
            font-size: 9px;
        }

        .ws-plate--lg .ws-plate-number {
            padding: 8px 14px;
            font-size: 20px;
            line-height: 24px;
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

        .ws-status-badge,
        .ws-shop-flag {
            max-width: 220px;
            padding: 5px 9px;
            display: inline-flex;
            align-items: center;
            border-radius: 5px;
            font-family: var(--ws-font-sans);
            font-size: 10.5px;
            font-weight: 600;
            line-height: 14px;
            letter-spacing: .11em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ws-status-badge--new {
            border: 1px solid var(--ws-status-new-border);
            background: var(--ws-status-new-bg);
            color: var(--ws-status-new-fg);
        }

        .ws-status-badge--in-progress {
            border: 1px solid var(--ws-status-progress-border);
            background: var(--ws-status-progress-bg);
            color: var(--ws-status-progress-fg);
        }

        .ws-status-badge--awaiting-parts {
            border: 1px solid var(--ws-status-awaiting-border);
            background: var(--ws-status-awaiting-bg);
            color: var(--ws-status-awaiting-fg);
        }

        .ws-status-badge--ready {
            border: 1px solid var(--ws-status-ready-border);
            background: var(--ws-status-ready-bg);
            color: var(--ws-status-ready-fg);
        }

        .ws-status-badge--completed {
            border: 1px solid var(--ws-status-completed-border);
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

        /* Dashboard-only visual system port. */
        .ws-dashboard {
            min-width: 0;
            font-family: var(--ws-font-sans);
        }

        .ws-dashboard-header {
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .ws-dashboard-title {
            margin: 0;
            color: var(--ws-text);
            font-family: var(--ws-font-display);
            font-size: 34px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -.2px;
        }

        .ws-dashboard-subtitle {
            margin: 7px 0 0;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 13.5px;
            line-height: 1.5;
        }

        .ws-system-status {
            min-height: 32px;
            padding: 6px 13px 6px 10px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid var(--ws-border);
            border-radius: 999px;
            background: var(--ws-card);
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            font-weight: 500;
            line-height: 16px;
            letter-spacing: 0;
            text-transform: none;
        }

        .ws-system-status-dot {
            width: 7px;
            height: 7px;
            flex: 0 0 7px;
            border-radius: 999px;
            background: var(--ws-status-new-fg);
            box-shadow: 0 0 0 3px rgb(30 122 92 / 15%);
        }

        .ws-dashboard .ws-dashboard-controls {
            margin-bottom: 24px;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
        }

        .ws-dashboard .ws-search-input {
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            font-family: var(--ws-font-sans);
        }

        .ws-dashboard .ws-primary-action {
            min-width: 168px;
            border-radius: 10px;
            background: var(--ws-primary);
            box-shadow: var(--shadow-sm);
            font-family: var(--ws-font-sans);
        }

        .ws-dashboard .ws-primary-action:hover {
            background: var(--ws-primary-hover);
            opacity: 1;
        }

        /* Three metrics: the in-shop count moved to the strip's own heading,
           where it is not a duplicate of the cards below it. */
        .ws-dashboard .ws-stat-grid {
            margin-bottom: 26px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        /* Compact operational metric: semantic rail, icon and strong figure. */
        .ws-dashboard .ws-stat-card {
            position: relative;
            min-width: 0;
            min-height: 84px;
            padding: 13px 16px;
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            align-items: center;
            gap: 13px;
            overflow: hidden;
            border: 1px solid var(--ws-border);
            border-radius: 10px;
            background: var(--ws-card);
            box-shadow: var(--shadow-sm);
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .ws-dashboard .ws-stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 4px;
            background: var(--ws-stat-accent, var(--ws-border-hover));
        }

        .ws-dashboard .ws-stat-card--alert::before {
            background: var(--ws-signal);
        }

        .ws-dashboard .ws-stat-card--appointments {
            --ws-stat-accent: var(--ws-stat-appointments-fg);
            --ws-stat-border: var(--ws-stat-appointments-border);
            --ws-stat-tint: var(--ws-stat-appointments-bg);
            border-color: var(--ws-stat-border);
            background: linear-gradient(120deg, var(--ws-stat-tint) 0%, var(--ws-card) 82%);
        }

        .ws-dashboard .ws-stat-card--kteo {
            --ws-stat-accent: var(--ws-status-expired-fg);
            --ws-stat-border: var(--ws-status-expired-border);
            --ws-stat-tint: var(--ws-status-expired-bg);
            border-color: var(--ws-stat-border);
            background: linear-gradient(120deg, var(--ws-stat-tint) 0%, var(--ws-card) 82%);
        }

        .ws-dashboard .ws-stat-card--parts {
            --ws-stat-accent: var(--ws-status-awaiting-fg);
            --ws-stat-border: var(--ws-status-awaiting-border);
            --ws-stat-tint: var(--ws-status-awaiting-bg);
            border-color: var(--ws-stat-border);
            background: linear-gradient(120deg, var(--ws-stat-tint) 0%, var(--ws-card) 82%);
        }

        .ws-dashboard .ws-stat-card:hover {
            border-color: var(--ws-stat-accent, var(--ws-border-hover));
            box-shadow: var(--shadow-md);
        }

        .ws-stat-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--ws-stat-border, var(--ws-border));
            border-radius: 10px;
            background: rgb(255 255 255 / 78%);
            color: var(--ws-stat-accent, var(--ws-text-muted));
            box-shadow: 0 5px 14px -10px currentColor;
        }

        .ws-stat-icon svg {
            width: 20px;
            height: 20px;
            stroke-width: 1.8;
        }

        .ws-stat-content {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .ws-dashboard .ws-stat-label {
            color: var(--ws-text-faint);
            font-family: var(--ws-font-sans);
            font-size: 10.5px;
            font-weight: 500;
            line-height: 15px;
            letter-spacing: .15em;
            text-transform: uppercase;
        }

        .ws-stat-row {
            margin-top: 4px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .ws-dashboard .ws-stat-value {
            color: var(--ws-stat-accent, var(--ws-text));
            font-family: var(--ws-font-display);
            font-size: 34px;
            font-weight: 700;
            line-height: .95;
            font-variant-numeric: tabular-nums;
        }

        .ws-dashboard .ws-stat-value--danger {
            color: var(--ws-signal);
        }

        /* ── Workshop strip ───────────────────────────────────────────
           A presentation view of the open work orders. The application
           models no physical bays, so nothing here is persisted and no
           free-position count is invented — each card is one open order. */
        .ws-bays {
            margin-bottom: 24px;
        }

        .ws-bays-head {
            margin-bottom: 11px;
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 4px 16px;
        }

        .ws-eyebrow {
            color: var(--ws-text-muted);
            font-family: var(--ws-font-display);
            font-size: 13px;
            font-weight: 700;
            line-height: 18px;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .ws-eyebrow b {
            color: var(--ws-text);
            font-weight: 700;
        }

        .ws-eyebrow--quiet {
            flex: 0 0 auto;
            color: var(--ws-text-faint);
            font-weight: 400;
            letter-spacing: .1em;
            transition: color 120ms ease;
        }

        a.ws-eyebrow--quiet:hover {
            color: var(--ws-text);
        }

        /* Cards sit in a fixed size band rather than stretching to fill the
           row, so a strip of one and a strip of eight look like the same
           object. Four per row on desktop is set explicitly below. */
        .ws-bay-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(224px, 280px));
            justify-content: start;
            align-items: stretch;
            gap: 14px;
        }

        .ws-bay {
            --ws-bay-accent: var(--ws-border-hover);
            --ws-bay-border: var(--ws-border);
            --ws-bay-tint: var(--ws-sunken);
            position: relative;
            min-width: 0;
            height: 100%;
            padding: 14px 16px 14px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            overflow: hidden;
            border: 1px solid var(--ws-bay-border);
            border-top: 3px solid var(--ws-bay-accent);
            border-radius: 10px;
            background: linear-gradient(155deg, var(--ws-bay-tint) 0%, var(--ws-card) 64%);
            box-shadow: 0 2px 4px rgb(13 42 47 / 7%), 0 18px 36px -26px rgb(13 42 47 / 50%);
            transition: transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
        }

        .ws-bay[data-status="new"] {
            --ws-bay-accent: var(--ws-status-new-fg);
            --ws-bay-border: var(--ws-status-new-border);
            --ws-bay-tint: var(--ws-status-new-bg);
        }

        .ws-bay[data-status="in_progress"] {
            --ws-bay-accent: var(--ws-status-progress-fg);
            --ws-bay-border: var(--ws-status-progress-border);
            --ws-bay-tint: var(--ws-status-progress-bg);
        }

        .ws-bay[data-status="awaiting_parts"] {
            --ws-bay-accent: var(--ws-status-awaiting-fg);
            --ws-bay-border: var(--ws-status-awaiting-border);
            --ws-bay-tint: var(--ws-status-awaiting-bg);
        }

        .ws-bay[data-status="ready"] {
            --ws-bay-accent: var(--ws-status-ready-fg);
            --ws-bay-border: var(--ws-status-ready-border);
            --ws-bay-tint: var(--ws-status-ready-bg);
        }

        .ws-bay:hover {
            border-color: var(--ws-bay-accent);
            transform: translateY(-2px);
            box-shadow: 0 3px 7px rgb(13 42 47 / 10%), 0 22px 42px -25px rgb(13 42 47 / 55%);
        }

        .ws-bay:focus-visible {
            outline: 2px solid var(--ws-text);
            outline-offset: -2px;
        }

        .ws-bay-number {
            position: absolute;
            top: 8px;
            right: 12px;
            color: var(--ws-bay-accent);
            font-family: var(--ws-font-display);
            font-size: 40px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -1px;
            opacity: .12;
        }

        .ws-bay-vehicle {
            max-width: 100%;
            margin: 11px 0 2px;
            overflow: hidden;
            color: var(--ws-text);
            font-size: 14.5px;
            font-weight: 500;
            line-height: 20px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* One line only: a two-line job description used to push the whole
           card taller and break the row's baseline. */
        .ws-bay-job {
            max-width: 100%;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 1;
            overflow: hidden;
            color: var(--ws-text-muted);
            font-size: 12.5px;
            line-height: 17px;
        }

        /* Four equal stages — παραλαβή, διάγνωση, εργασία, έτοιμο. Equal
           widths on every card, never a percentage of anything. */
        .ws-bay-rail {
            width: 100%;
            margin-top: 11px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3px;
        }

        .ws-bay-rail-step {
            height: 4px;
            border-radius: 99px;
            background: #E7EBE6;
        }

        .ws-bay[data-status="new"] .ws-bay-rail-step.is-filled {
            background: var(--ws-status-new-fg);
        }

        .ws-bay[data-status="in_progress"] .ws-bay-rail-step.is-filled {
            background: var(--ws-status-progress-fg);
        }

        .ws-bay[data-status="awaiting_parts"] .ws-bay-rail-step.is-filled {
            background: var(--ws-status-awaiting-fg);
        }

        .ws-bay[data-status="ready"] .ws-bay-rail-step.is-filled {
            background: var(--ws-status-ready-fg);
        }

        /* Long Greek status labels wrap onto their own line rather than
           squeezing the elapsed time into an ellipsis. The auto top margin
           pins the footer to the card's bottom edge whatever sits above it. */
        .ws-bay-foot {
            width: 100%;
            margin-top: auto;
            padding-top: 13px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px 10px;
        }

        .ws-bay-elapsed {
            flex: 0 0 auto;
            color: var(--ws-text-faint);
            font-family: var(--ws-font-mono);
            font-size: 12px;
            line-height: 16px;
            white-space: nowrap;
        }

        .ws-bay-foot .ws-status-badge {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ws-dashboard-body {
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 22px;
        }

        .ws-dashboard-primary,
        .ws-dashboard-aside {
            min-width: 0;
        }

        .ws-dashboard-aside {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        /* The donor keeps the section head inside the panel, above a hairline. */
        .ws-dashboard .ws-section-header {
            min-height: 32px;
            margin-bottom: 0;
            padding: 17px 20px 15px;
            align-items: baseline;
            border-bottom: 1px solid var(--ws-border);
        }

        .ws-dashboard .ws-section-header h2,
        .ws-dashboard-section-title {
            margin: 0;
            color: var(--ws-text);
            font-family: var(--ws-font-display);
            font-size: 19px;
            font-weight: 700;
            line-height: 26px;
            letter-spacing: 0;
        }

        .ws-dashboard .ws-section-header a {
            min-height: 0;
            padding: 0 0 1px;
            color: var(--ws-text-muted);
            border-bottom: 1px solid var(--ws-border-hover);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            font-weight: 400;
        }

        .ws-dashboard .ws-section-header a:hover {
            color: var(--ws-text);
            border-bottom-color: var(--ws-text);
        }

        .ws-dashboard .ws-section-header a > [aria-hidden="true"] {
            display: none;
        }

        .ws-dashboard-section-title {
            margin-bottom: 11px;
        }

        .ws-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--ws-border);
            border-radius: 10px;
            background: var(--ws-card);
            box-shadow: var(--shadow-sm);
        }

        .ws-dashboard .ws-panel {
            border-color: var(--ws-border-hover);
            box-shadow: 0 2px 4px rgb(13 42 47 / 5%), 0 18px 40px -28px rgb(13 42 47 / 42%);
        }

        .ws-dashboard .ws-panel > .ws-section-header {
            background: linear-gradient(90deg, #FAFCF9 0%, var(--ws-card) 72%);
        }

        /* Shared page-composition primitives for workshop screens. */
        .ws-page-hero {
            margin-bottom: 32px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
        }

        .ws-page-hero-copy {
            min-width: 0;
        }

        .ws-page-eyebrow {
            margin: 0 0 6px;
            color: var(--ws-primary);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            font-weight: 700;
            line-height: 18px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .ws-page-display-title {
            margin: 0;
            color: var(--ws-text);
            font-family: var(--ws-font-serif);
            font-size: 32px;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -.025em;
            overflow-wrap: anywhere;
        }

        .ws-page-subtitle {
            margin: 7px 0 0;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-serif);
            font-size: 15px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .ws-page-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .ws-page-actions .ws-primary-action {
            min-width: 168px;
            min-height: 44px;
            border-radius: 10px;
            background: var(--ws-primary);
            box-shadow: var(--shadow-sm);
            font-family: var(--ws-font-sans);
        }

        .ws-page-actions .ws-primary-action:hover {
            background: var(--ws-primary-hover);
            opacity: 1;
        }

        .ws-secondary-action {
            min-height: 42px;
            padding: 9px 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid var(--ws-border);
            border-radius: 10px;
            background: var(--ws-card);
            color: var(--ws-text);
            box-shadow: var(--shadow-sm);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            font-weight: 600;
            transition: border-color 140ms ease, background-color 140ms ease;
        }

        .ws-secondary-action:hover {
            border-color: var(--ws-border-hover);
            background: var(--ws-sunken);
        }

        .ws-secondary-action svg {
            width: 17px;
            height: 17px;
            stroke-width: 1.9;
        }

        .ws-back-link {
            min-height: 40px;
            margin-bottom: 18px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            font-weight: 600;
            transition: color 140ms ease;
        }

        .ws-back-link:hover {
            color: var(--ws-primary);
        }

        .ws-back-link svg {
            width: 16px;
            height: 16px;
            stroke-width: 2;
        }

        .ws-feedback-success {
            min-height: 48px;
            margin-bottom: 18px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--ws-status-ready-fg);
            border-radius: 10px;
            background: var(--ws-status-ready-bg);
            color: var(--ws-status-ready-fg);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            font-weight: 600;
        }

        .ws-feedback-success svg {
            width: 17px;
            height: 17px;
            flex: 0 0 17px;
            stroke-width: 2.2;
        }

        .ws-feedback-error {
            min-height: 48px;
            margin-bottom: 18px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            border: 1px solid var(--ws-status-expired-fg);
            border-radius: 10px;
            background: var(--ws-status-expired-bg);
            color: var(--ws-status-expired-fg);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            font-weight: 600;
        }

        .ws-panel-head {
            min-height: 58px;
            padding: 15px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid var(--ws-border);
        }

        .ws-panel-title {
            margin: 0;
            color: var(--ws-text);
            font-family: var(--ws-font-serif);
            font-size: 19px;
            font-weight: 700;
            line-height: 26px;
            letter-spacing: -.012em;
        }

        .ws-panel-meta {
            flex-shrink: 0;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            font-weight: 600;
            line-height: 18px;
            white-space: nowrap;
        }

        .ws-field-label {
            margin-bottom: 5px;
            display: block;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 10px;
            font-weight: 700;
            line-height: 14px;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .ws-sunken-card {
            min-width: 0;
            padding: 14px;
            border-radius: 12px;
            background: var(--ws-sunken);
        }

        .ws-info-list {
            margin: 0;
            padding: 2px 18px;
        }

        .ws-info-row {
            min-width: 0;
            padding: 13px 0;
            display: grid;
            grid-template-columns: minmax(105px, .8fr) minmax(0, 1.2fr);
            align-items: start;
            gap: 14px;
            border-bottom: 1px solid var(--ws-border);
        }

        .ws-info-row:last-child {
            border-bottom: 0;
        }

        .ws-info-row dt,
        .ws-info-row dd {
            margin: 0;
        }

        .ws-info-row dt {
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            font-weight: 600;
            line-height: 19px;
        }

        .ws-info-row dd {
            min-width: 0;
            color: var(--ws-text);
            font-family: var(--ws-font-serif);
            font-size: 14px;
            font-weight: 700;
            line-height: 20px;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .ws-card-grid {
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: stretch;
            gap: 14px;
        }

        .ws-clickable-card {
            height: 100%;
            color: var(--ws-text);
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .ws-clickable-card:hover {
            border-color: var(--ws-border-hover);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .ws-clickable-card:focus-visible {
            border-color: var(--ws-primary);
            outline: 2px solid var(--ws-primary);
            outline-offset: 2px;
        }

        .ws-operational-card {
            position: relative;
            min-width: 0;
            min-height: 176px;
            height: 100%;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: border-color 140ms ease, background-color 140ms ease, box-shadow 140ms ease;
        }

        .ws-operational-card:hover,
        .ws-operational-card:focus-within {
            border-color: var(--ws-border-hover);
            background: var(--ws-sunken);
        }

        .ws-operational-card__overlay {
            position: absolute;
            z-index: 1;
            inset: 0;
            border-radius: inherit;
        }

        .ws-operational-card__overlay:focus-visible {
            outline: 2px solid var(--ws-primary);
            outline-offset: -2px;
        }

        .ws-operational-card__head {
            min-width: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .ws-operational-card__head > .ws-operational-card__identity {
            flex: 1 1 180px;
        }

        .ws-operational-card__identity,
        .ws-operational-card__body {
            min-width: 0;
        }

        .ws-operational-card__body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ws-operational-card__title {
            margin: 0;
            overflow: hidden;
            color: var(--ws-text);
            font-family: var(--ws-font-serif);
            font-size: 15px;
            font-weight: 700;
            line-height: 20px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-operational-card__subtitle {
            display: block;
            min-width: 0;
            overflow: hidden;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            line-height: 18px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-operational-card__count {
            flex: 0 0 auto;
            max-width: 100%;
            padding: 3px 8px;
            overflow: hidden;
            border: 1px solid var(--ws-border);
            border-radius: 8px;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            font-weight: 600;
            line-height: 18px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-operational-card__meta {
            min-width: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px 12px;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            line-height: 18px;
        }

        .ws-operational-card__truncate {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-operational-card__plate-list {
            min-width: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .ws-operational-card .ws-plate {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ws-operational-card__interactive {
            position: relative;
            z-index: 2;
        }

        .ws-operational-card__inline-action {
            color: var(--ws-text-muted);
        }

        .ws-operational-card__inline-action:hover {
            color: var(--ws-text);
            text-decoration: underline;
        }

        .ws-operational-card__footer {
            position: relative;
            z-index: 2;
            margin-top: auto;
            padding-top: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            border-top: 1px solid var(--ws-border);
        }

        .ws-card-action {
            min-height: 36px;
            padding: 7px 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border: 1px solid var(--ws-border);
            border-radius: 8px;
            background: var(--ws-card);
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            font-weight: 600;
            line-height: 18px;
            white-space: nowrap;
            transition: color 120ms ease, border-color 120ms ease, background-color 120ms ease;
        }

        .ws-card-action:hover {
            border-color: var(--ws-border-hover);
            background: var(--ws-card);
            color: var(--ws-text);
        }

        .ws-card-action--primary {
            border-color: transparent;
            background: var(--ws-primary);
            color: var(--ws-primary-fg);
        }

        .ws-card-action--primary:hover {
            border-color: transparent;
            background: var(--ws-primary-hover);
            color: var(--ws-primary-fg);
        }

        .ws-card-action svg {
            width: 13px;
            height: 13px;
            stroke-width: 2.5;
        }

        .ws-pagination {
            min-width: 0;
            margin-top: 16px;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            line-height: 18px;
        }

        .ws-pagination nav,
        .ws-pagination nav > div {
            min-width: 0;
        }

        .ws-pagination nav > div:first-child {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .ws-pagination nav > div:last-child {
            display: none;
        }

        .ws-pagination p {
            margin: 0;
            color: var(--ws-text-muted);
        }

        .ws-pagination nav a,
        .ws-pagination nav [aria-disabled="true"] > span,
        .ws-pagination nav [aria-current="page"] > span {
            min-width: 36px;
            min-height: 36px;
            padding: 7px 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--ws-border);
            border-radius: 8px;
            background: var(--ws-card);
            color: var(--ws-text-muted);
            white-space: nowrap;
        }

        .ws-pagination nav a:hover {
            border-color: var(--ws-border-hover);
            background: var(--ws-sunken);
            color: var(--ws-text);
        }

        .ws-pagination nav [aria-disabled="true"] > span {
            color: var(--ws-text-faint);
            cursor: default;
        }

        .ws-pagination nav [aria-current="page"] > span {
            background: var(--ws-sunken);
            color: var(--ws-text);
            font-weight: 700;
        }

        .ws-pagination svg.w-5.h-5 {
            width: 18px;
            height: 18px;
            display: block;
            flex: 0 0 18px;
        }

        @media (min-width: 640px) {
            .ws-pagination nav > div:first-child {
                display: none;
            }

            .ws-pagination nav > div:last-child {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 12px 20px;
            }

            .ws-pagination nav > div:last-child > div:last-child,
            .ws-pagination nav > div:last-child > div:last-child > span {
                min-width: 0;
                max-width: 100%;
            }

            .ws-pagination nav > div:last-child > div:last-child > span {
                display: inline-flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 4px;
            }
        }

        @media (min-width: 1024px) {
            .ws-card-grid--two-up {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .ws-page-hero {
                margin-bottom: 24px;
                align-items: stretch;
                flex-direction: column;
                gap: 16px;
            }

            .ws-page-display-title {
                font-size: 28px;
            }

            .ws-page-actions {
                justify-content: space-between;
            }

            .ws-panel-head {
                padding-right: 16px;
                padding-left: 16px;
            }
        }

        .ws-recent-orders-head {
            display: none;
        }

        /* Donor open-order row: plate, who/what, then the compact right rail.
           The left edge carries the status colour. */
        .ws-recent-order {
            min-width: 0;
            padding: 15px 20px 15px 17px;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: center;
            gap: 9px;
            border-bottom: 1px solid var(--ws-divider-soft);
            border-left: 4px solid transparent;
            background: var(--ws-card);
            transition: background-color 140ms ease;
        }

        .ws-recent-order:last-child {
            border-bottom: 0;
        }

        .ws-recent-order:hover {
            background: #F7FAF7;
        }

        .ws-recent-order[data-status="new"] {
            border-left-color: var(--ws-status-new-fg);
        }

        .ws-recent-order[data-status="in_progress"] {
            border-left-color: var(--ws-status-progress-fg);
        }

        .ws-recent-order[data-status="awaiting_parts"] {
            border-left-color: var(--ws-status-awaiting-fg);
        }

        .ws-recent-order[data-status="ready"] {
            border-left-color: var(--ws-status-ready-fg);
        }

        .ws-recent-order .ws-plate {
            justify-self: start;
        }

        .ws-recent-order-number {
            color: var(--ws-text-faint);
            font-family: var(--ws-font-mono);
            font-size: 12.5px;
            font-weight: 500;
            line-height: 18px;
        }

        .ws-recent-order-body {
            min-width: 0;
        }

        .ws-recent-order-customer,
        .ws-recent-order-meta {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-recent-order-customer {
            color: var(--ws-text);
            font-family: var(--ws-font-sans);
            font-size: 15px;
            font-weight: 500;
            line-height: 21px;
        }

        .ws-recent-order-meta {
            margin-top: 2px;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            line-height: 18px;
        }

        .ws-recent-order-aside {
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .ws-recent-order-status {
            min-width: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        /* Same badge shape as a status, in the neutral grey pair — it explains
           an absence from the strip above, it is not a state to act on. */
        .ws-shop-flag {
            border: 1px solid var(--ws-status-completed-border);
            background: var(--ws-status-completed-bg);
            color: var(--ws-status-completed-fg);
        }

        .ws-dashboard .ws-status-badge,
        .ws-dashboard .ws-shop-flag {
            max-width: 100%;
            overflow: hidden;
            font-family: var(--ws-font-sans);
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        .ws-recent-order-time {
            color: var(--ws-text-muted);
            font-family: var(--ws-font-mono);
            font-size: 12.5px;
            line-height: 18px;
            white-space: nowrap;
        }

        .ws-recent-order-chevron {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            color: var(--ws-text-faint);
            stroke-width: 1.8;
            transition: color 140ms ease, transform 140ms ease;
        }

        .ws-recent-order:hover .ws-recent-order-chevron {
            color: var(--ws-text);
            transform: translateX(2px);
        }

        /* Donor quick-entry tiles: a hairline-separated three-up strip. */
        .ws-quick-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1px;
            background: var(--ws-border);
        }

        .ws-quick-action {
            min-height: 92px;
            padding: 18px 10px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 9px;
            background: var(--ws-card);
            font-family: var(--ws-font-sans);
            text-align: center;
            transition: background-color 120ms ease;
        }

        .ws-quick-action:hover {
            background: var(--ws-sunken);
        }

        .ws-quick-action-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 9px;
        }

        .ws-quick-action-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #EEF3ED;
            color: var(--ws-text);
        }

        .ws-quick-action-icon svg {
            width: 19px;
            height: 19px;
            stroke-width: 1.8;
        }

        .ws-quick-action-label {
            max-width: 100%;
            overflow: hidden;
            color: var(--ws-text);
            font-size: 13px;
            font-weight: 400;
            line-height: 18px;
            text-overflow: ellipsis;
        }

        .ws-quick-action-plus {
            display: none;
        }

        .ws-dashboard-kteo .ws-list {
            overflow: hidden;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .ws-dashboard-kteo .ws-kteo-row {
            min-width: 0;
            min-height: 58px;
            padding: 13px 18px;
            gap: 13px;
            border-bottom-color: var(--ws-divider-soft);
        }

        /* Lapsed and upcoming ΚΤΕΟ are different jobs; the sticky sub-header
           keeps which one you are reading visible while the list scrolls. */
        .ws-kteo-subhead {
            position: sticky;
            z-index: 1;
            top: 0;
            margin: 0;
            padding: 9px 18px 7px;
            display: flex;
            align-items: baseline;
            gap: 6px;
            border-top: 1px solid var(--ws-border);
            background: #FAFCF9;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-display);
            font-size: 11.5px;
            font-weight: 700;
            line-height: 16px;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .ws-kteo-subhead:first-of-type {
            border-top: 0;
        }

        .ws-kteo-subhead--expired {
            background: linear-gradient(90deg, var(--ws-status-expired-bg) 0%, var(--ws-card) 72%);
            color: var(--ws-signal);
        }

        .ws-kteo-subhead span {
            font-weight: 700;
        }

        .ws-dashboard .ws-empty-state {
            min-height: 132px;
            font-family: var(--ws-font-sans);
        }

        @media (min-width: 1024px) {
            .ws-dashboard .ws-stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 20px;
            }

            .ws-dashboard .ws-stat-card {
                min-height: 88px;
                padding: 13px 17px;
            }

            .ws-recent-order {
                min-height: 72px;
                padding: 15px 20px 15px 17px;
                grid-template-columns: 124px minmax(0, 1fr) auto;
                gap: 16px;
            }

            .ws-recent-order-aside {
                justify-content: flex-end;
                gap: 16px;
            }

            .ws-recent-order-status {
                overflow: hidden;
            }
        }

        @media (min-width: 1200px) {
            .ws-dashboard-body {
                grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
                gap: 22px;
                align-items: start;
            }
        }

        /* Once four cards can hold their minimum width, pin the strip to four
           per row so eight orders read as two tidy rows that line up with the
           metrics and panels below instead of ending short of them. */
        @media (min-width: 1280px) {
            .ws-bay-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1120px) {
            .ws-work-order-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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

        /* Below 768px the rail is removed from the flow entirely and becomes an
           off-canvas drawer, so the content keeps the full viewport width. */
        @media (max-width: 767px) {
            .ws-mobile-header {
                position: sticky;
                z-index: 30;
                top: 0;
                height: var(--header-h);
                padding: 0 8px 0 4px;
                display: flex;
                align-items: center;
                gap: 12px;
                background: var(--ws-nav);
                color: var(--ws-nav-text-hi);
            }

            .ws-mobile-brand {
                margin-left: auto;
                padding-right: 8px;
                color: var(--ws-nav-text);
                font-size: 14px;
                font-weight: 500;
            }

            .ws-drawer-backdrop {
                position: fixed;
                z-index: 40;
                inset: 0;
                display: block;
                background: rgb(24 24 27 / 45%);
            }

            .ws-sidebar {
                position: fixed;
                z-index: 50;
                top: 0;
                left: 0;
                width: 272px;
                max-width: 82vw;
                height: 100dvh;
                padding: 16px 12px;
                overflow-y: auto;
                visibility: hidden;
                transform: translateX(-100%);
                transition: transform 220ms ease, visibility 0s linear 220ms;
            }

            .ws-sidebar--open {
                visibility: visible;
                transform: translateX(0);
                transition: transform 220ms ease;
            }

            .ws-sidebar-brand {
                padding: 0 56px 0 12px;
                justify-content: flex-start;
            }

            .ws-sidebar-brand-full {
                display: block;
            }

            .ws-sidebar-brand-compact {
                display: none;
            }

            .ws-sidebar-item {
                min-height: 48px;
                padding: 12px;
                flex-direction: row;
                justify-content: flex-start;
                gap: 12px;
                font-size: 14px;
                line-height: 20px;
                text-align: left;
            }

            .ws-drawer-close {
                position: absolute;
                top: 12px;
                right: 12px;
                width: 44px;
                height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 8px;
                background: transparent;
                color: var(--ws-nav-text-hi);
                cursor: pointer;
            }

            .ws-drawer-close:hover {
                background: var(--ws-nav-hover);
            }

            .ws-drawer-close svg {
                width: 20px;
                height: 20px;
                stroke-width: 1.8;
            }

            .ws-content {
                width: 100%;
            }

            .ws-main {
                max-width: none;
                margin: 0;
                padding: 16px 16px 32px;
            }

            .ws-mobile-header {
                border-bottom: 1px solid var(--ws-nav-divider);
                box-shadow: var(--shadow-sm);
            }

            .ws-sidebar {
                box-shadow: 16px 0 40px rgb(17 24 39 / 18%);
            }

            .ws-dashboard-header {
                margin-bottom: 24px;
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }

            .ws-dashboard-title {
                font-size: 28px;
                line-height: 34px;
            }

            .ws-system-status {
                min-height: 30px;
                padding: 6px 11px;
            }

            .ws-dashboard .ws-dashboard-controls {
                margin-bottom: 24px;
                grid-template-columns: minmax(0, 1fr);
            }

            .ws-dashboard .ws-primary-action {
                width: 100%;
            }

            .ws-dashboard .ws-stat-grid {
                margin-bottom: 24px;
                gap: 12px;
            }

            .ws-dashboard .ws-stat-card {
                min-height: 80px;
                padding: 12px 14px;
                gap: 12px;
            }

            .ws-dashboard .ws-stat-label {
                font-size: 10px;
                line-height: 14px;
            }

            .ws-dashboard .ws-stat-value {
                font-size: 31px;
            }

            .ws-dashboard-body {
                gap: 20px;
            }

            .ws-recent-order {
                padding: 14px 15px 14px 12px;
            }

            .ws-eyebrow {
                font-size: 12px;
                letter-spacing: .12em;
            }

            .ws-bay {
                padding: 14px 15px 13px;
            }

            .ws-bay-number {
                font-size: 34px;
            }

            .ws-quick-action {
                min-height: 84px;
                padding: 15px 6px 13px;
            }

            .ws-quick-action-label {
                font-size: 12px;
                line-height: 16px;
            }
            body {
                --nav-h: 68px;
            }

            :root {
                --ws-page: #E9EDE8;
            }

            .ws-mobile-header {
                height: calc(56px + env(safe-area-inset-top, 0px));
                padding: env(safe-area-inset-top, 0px) 10px 0;
                gap: 8px;
            }

            .ws-mobile-context {
                min-width: 0;
                padding-left: 6px;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 1px;
            }

            .ws-mobile-context-kicker,
            .ws-mobile-context-title {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .ws-mobile-context-kicker {
                color: var(--ws-nav-text);
                font-size: 10px;
                font-weight: 700;
                line-height: 14px;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .ws-mobile-context-title {
                color: var(--ws-nav-text-hi);
                font-size: 17px;
                font-weight: 700;
                line-height: 22px;
            }

            .ws-mobile-header-action {
                width: 44px;
                height: 44px;
                padding: 0;
                flex: 0 0 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 12px;
                background: transparent;
                color: var(--ws-nav-text-hi);
                transition: color 120ms ease, background-color 120ms ease;
            }

            .ws-mobile-back-action {
                color: var(--ws-nav-text-hi);
            }

            .ws-mobile-header-action:hover {
                background: var(--ws-nav-hover);
                color: var(--ws-nav-text-hi);
            }

            .ws-mobile-header-action svg {
                width: 21px;
                height: 21px;
                stroke-width: 1.9;
            }

            .ws-mobile-bottom-nav {
                position: fixed;
                z-index: 35;
                right: 0;
                bottom: 0;
                left: 0;
                min-height: calc(var(--nav-h) + env(safe-area-inset-bottom, 0px));
                padding: 6px max(6px, env(safe-area-inset-right, 0px)) env(safe-area-inset-bottom, 0px) max(6px, env(safe-area-inset-left, 0px));
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                align-items: end;
                border-top: 1px solid var(--ws-border);
                background: var(--ws-card);
                box-shadow: 0 -8px 24px rgb(17 24 39 / 8%);
            }

            .ws-mobile-bottom-item {
                min-width: 0;
                min-height: 56px;
                padding: 5px 2px 4px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 3px;
                border: 0;
                border-radius: 12px;
                background: transparent;
                color: #4A6360;
                font-family: var(--ws-font-sans);
                font-size: 9px;
                font-weight: 600;
                line-height: 12px;
                text-align: center;
                cursor: pointer;
            }

            .ws-mobile-bottom-item:hover,
            .ws-mobile-bottom-item[aria-current="page"] {
                background: #EEF3ED;
                color: var(--ws-primary);
            }

            .ws-mobile-bottom-icon {
                width: 24px;
                height: 24px;
                flex: 0 0 24px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .ws-mobile-bottom-icon svg {
                width: 22px;
                height: 22px;
                stroke-width: 1.8;
            }

            .ws-mobile-bottom-label {
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .ws-mobile-bottom-item--create {
                color: var(--ws-primary);
            }

            .ws-mobile-bottom-item--create:hover,
            .ws-mobile-bottom-item--create[aria-current="page"] {
                background: transparent;
            }

            .ws-mobile-bottom-item--create .ws-mobile-bottom-icon {
                width: 48px;
                height: 48px;
                margin-top: -22px;
                flex-basis: 48px;
                border: 4px solid var(--ws-card);
                border-radius: 999px;
                background: var(--ws-primary);
                color: var(--ws-primary-fg);
                box-shadow: 0 6px 16px rgb(21 93 252 / 24%);
            }

            .ws-mobile-bottom-item--create .ws-mobile-bottom-icon svg {
                width: 22px;
                height: 22px;
                stroke-width: 2.2;
            }

            .ws-main {
                padding: 8px 16px 32px;
                padding-bottom: calc(32px + var(--nav-h) + env(safe-area-inset-bottom, 0px));
            }

            .ws-dashboard-header {
                margin-bottom: 10px;
                align-items: center;
                flex-direction: row;
                gap: 10px;
            }

            .ws-dashboard-title {
                display: none;
            }

            .ws-dashboard-header > div {
                min-width: 0;
                flex: 1;
            }

            .ws-dashboard-subtitle {
                margin: 0;
                font-family: var(--ws-font-sans);
                font-size: 12px;
                line-height: 18px;
            }

            .ws-dashboard-subtitle-prefix {
                display: none;
            }

            .ws-dashboard-date {
                display: block;
            }

            .ws-system-status {
                display: none;
            }

            .ws-dashboard .ws-dashboard-controls {
                margin-bottom: 14px;
            }

            .ws-dashboard .ws-dashboard-controls > .ws-primary-action {
                display: none;
            }

            .ws-dashboard .ws-search-input {
                height: 52px;
                padding-left: 52px;
                border-radius: 14px;
                font-size: 14px;
            }

            .ws-dashboard .ws-search-submit {
                top: 4px;
                left: 4px;
                width: 44px;
                height: 44px;
                border-radius: 10px;
            }

            .ws-dashboard .ws-stat-grid {
                margin-bottom: 28px;
                gap: 12px;
            }

            .ws-dashboard .ws-stat-card {
                min-height: 74px;
                padding: 11px 13px;
                grid-template-columns: 38px minmax(0, 1fr);
                gap: 11px;
                border-radius: 10px;
            }

            .ws-stat-icon {
                width: 38px;
                height: 38px;
            }

            .ws-dashboard .ws-stat-label {
                font-size: 9px;
                line-height: 12px;
                letter-spacing: 0;
                text-transform: none;
            }

            .ws-dashboard .ws-stat-value {
                font-size: 26px;
                line-height: 28px;
            }

            .ws-dashboard-body {
                gap: 24px;
            }

            .ws-dashboard .ws-section-header {
                min-height: 44px;
                margin-bottom: 8px;
                gap: 12px;
                flex-wrap: nowrap;
            }

            .ws-dashboard-primary .ws-section-header h2 {
                min-width: 0;
                white-space: nowrap;
            }

            .ws-dashboard-primary .ws-section-header a {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            /* Only the trailing link shortens on a phone. The heading keeps
               its one true name at every width. */
            .ws-dashboard-primary .ws-section-header a .ws-section-label-desktop {
                display: none;
            }

            .ws-dashboard-primary .ws-section-header a .ws-section-label-mobile {
                display: inline;
            }

            .ws-dashboard .ws-section-header h2,
            .ws-dashboard-section-title {
                font-size: 18px;
                line-height: 24px;
            }

            .ws-recent-order {
                min-height: 72px;
                padding: 12px 12px 12px 10px;
                gap: 8px;
            }

            .ws-recent-order-number {
                font-size: 12px;
            }

            .ws-recent-order-customer {
                font-size: 14px;
                line-height: 19px;
            }

            .ws-recent-order-meta {
                margin-top: 1px;
                font-size: 12px;
                line-height: 16px;
            }

            .ws-recent-order-status {
                max-width: 104px;
            }

            .ws-dashboard .ws-status-badge,
            .ws-dashboard .ws-shop-flag {
                padding: 2px 6px;
                font-size: 9px;
                line-height: 14px;
                letter-spacing: 0;
                text-transform: none;
            }

            .ws-recent-order-time {
                font-size: 11px;
                line-height: 14px;
            }

            .ws-main > .woc-toolbar,
            .ws-main .wos-page > .ws-back-link {
                display: none;
            }

            .ws-section-header a,
            .ws-empty-action a,
            .ws-back-link,
            .ws-secondary-action {
                min-height: 44px;
            }

            .ws-icon-action {
                width: 44px;
                height: 44px;
            }


            body.ws-drawer-open {
                overflow: hidden;
            }
        }

        /* On a phone, three columns squeeze the labels into broken stacks and
           bury the expired-ΚΤΕΟ figure — the one number worth acting on. One
           per row keeps every label on its line and the count legible. */
        @media (max-width: 639px) {
            .ws-dashboard .ws-stat-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            /* Drop the 280px cap: at phone widths only one or two columns fit,
               and a capped card sits narrower than everything stacked below
               it. Let the tracks take the width instead. */
            .ws-bay-grid {
                grid-template-columns: repeat(auto-fill, minmax(224px, 1fr));
            }

            /* A full-width cell has room for the label again, so it goes back
               to the readable size the cramped three-up layout gave up. */
            .ws-dashboard .ws-stat-label {
                font-size: 11px;
                line-height: 15px;
                letter-spacing: .12em;
                text-transform: uppercase;
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
        </div>

        <a href="{{ route('workshop.search') }}" class="ws-mobile-header-action" aria-label="Αναζήτηση οχήματος" title="Αναζήτηση οχήματος">
            <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <circle cx="10.75" cy="10.75" r="6.75" />
                <path stroke-linecap="round" d="m15.75 15.75 4.5 4.5" />
            </svg>
        </a>
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
