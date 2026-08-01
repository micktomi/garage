<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F9FAFB">
    <title>@yield('title', 'Συνεργείο')</title>
    <style>
        :root {
            /* Surfaces */
            --ws-page: #F9FAFB;
            --ws-card: #FFFFFF;
            --ws-sunken: #F3F4F6;

            /* Text */
            --ws-text: #111827;
            --ws-text-muted: #6B7280;
            --ws-text-faint: #9CA3AF;

            /* Lines */
            --ws-border: #E5E7EB;
            --ws-border-hover: #D1D5DB;

            /* Sidebar */
            --ws-nav: #FFFFFF;
            --ws-nav-hover: #F3F4F6;
            --ws-nav-active: #111827;
            --ws-nav-text: #4B5563;
            --ws-nav-text-hi: #111827;
            --ws-nav-divider: #E5E7EB;

            /* Screenshot-matched primary action. */
            --ws-primary: #155DFC;
            --ws-primary-hover: #1447E6;
            --ws-primary-fg: #FFFFFF;

            /* Explicit Greek-capable system stacks. */
            --ws-font-sans: ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans", Arial, sans-serif;
            --ws-font-serif: Georgia, "Times New Roman", "Noto Serif", serif;

            /* Work-order statuses */
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
            background: var(--ws-page);
            -webkit-text-size-adjust: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--ws-page);
            color: var(--ws-text);
            font-family: var(--ws-font-sans);
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

        [x-cloak] {
            display: none !important;
        }

        /* Mobile-only shell pieces. Hidden on desktop, so the desktop layout is untouched. */
        .ws-mobile-header,
        .ws-mobile-menu-trigger,
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
            padding: 16px 12px;
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
            margin-bottom: 12px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
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

        .ws-sidebar-brand-full {
            display: block;
        }

        .ws-sidebar-brand-compact {
            display: none;
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
            min-height: 44px;
            padding: 10px 12px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
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
            max-width: 1216px;
            margin: 0 auto;
            padding: 40px 32px 48px;
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
            border: 1px solid var(--ws-border);
            background: transparent;
            color: var(--ws-text-muted);
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

        /* Dashboard-only visual system port. */
        .ws-dashboard {
            min-width: 0;
            font-family: var(--ws-font-serif);
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
            font-family: var(--ws-font-serif);
            font-size: 32px;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.025em;
        }

        .ws-dashboard-subtitle {
            margin: 6px 0 0;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-serif);
            font-size: 15px;
            line-height: 1.5;
        }

        .ws-system-status {
            min-height: 32px;
            padding: 7px 13px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #A7F3D0;
            border-radius: 999px;
            background: #ECFDF5;
            color: #047857;
            font-family: var(--ws-font-sans);
            font-size: 11px;
            font-weight: 700;
            line-height: 16px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .ws-system-status-dot {
            width: 8px;
            height: 8px;
            flex: 0 0 8px;
            border-radius: 999px;
            background: #10B981;
        }

        .ws-dashboard .ws-dashboard-controls {
            margin-bottom: 32px;
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

        .ws-dashboard .ws-stat-grid {
            margin-bottom: 48px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .ws-dashboard .ws-stat-card {
            min-width: 0;
            min-height: 152px;
            padding: 20px;
            justify-content: flex-start;
            gap: 14px;
            border: 1px solid var(--ws-border);
            border-radius: 16px;
            background: var(--ws-card);
            box-shadow: var(--shadow-sm);
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .ws-dashboard .ws-stat-card:hover {
            border-color: var(--ws-border-hover);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .ws-stat-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .ws-stat-icon svg {
            width: 24px;
            height: 24px;
            stroke-width: 1.8;
        }

        .ws-stat-card--blue .ws-stat-icon {
            background: #EFF6FF;
            color: #2563EB;
        }

        .ws-stat-card--violet .ws-stat-icon {
            background: #F5F3FF;
            color: #7C3AED;
        }

        .ws-stat-card--amber .ws-stat-icon {
            background: #FFFBEB;
            color: #D97706;
        }

        .ws-stat-card--rose .ws-stat-icon {
            background: #FFF1F2;
            color: #E11D48;
        }

        .ws-dashboard .ws-stat-copy {
            display: flex;
            flex-direction: column;
        }

        .ws-dashboard .ws-stat-label {
            color: var(--ws-text-muted);
            font-family: var(--ws-font-sans);
            font-size: 12px;
            font-weight: 600;
            line-height: 16px;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .ws-dashboard .ws-stat-value {
            margin-top: 3px;
            color: var(--ws-text);
            font-family: var(--ws-font-serif);
            font-size: 32px;
            font-weight: 700;
            line-height: 36px;
        }

        .ws-dashboard .ws-stat-value--danger {
            color: var(--ws-status-expired-fg);
        }

        .ws-dashboard-body {
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 32px;
        }

        .ws-dashboard-primary,
        .ws-dashboard-aside {
            min-width: 0;
        }

        .ws-dashboard-aside {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .ws-dashboard .ws-section-header {
            min-height: 32px;
            margin-bottom: 16px;
        }

        .ws-dashboard .ws-section-header h2,
        .ws-dashboard-section-title {
            margin: 0;
            color: var(--ws-text);
            font-family: var(--ws-font-serif);
            font-size: 20px;
            font-weight: 700;
            line-height: 28px;
            letter-spacing: -0.015em;
        }

        .ws-dashboard .ws-section-header a {
            color: var(--ws-primary);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            font-weight: 600;
        }

        .ws-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--ws-border);
            border-radius: 16px;
            background: var(--ws-card);
            box-shadow: var(--shadow-sm);
        }

        .ws-recent-orders-head {
            display: none;
        }

        .ws-recent-order {
            min-width: 0;
            padding: 16px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            grid-template-areas:
                "number body chevron"
                ". status chevron"
                ". time chevron";
            align-items: center;
            gap: 4px 12px;
            border-bottom: 1px solid var(--ws-border);
            transition: background-color 140ms ease;
        }

        .ws-recent-order:last-child {
            border-bottom: 0;
        }

        .ws-recent-order:hover {
            background: var(--ws-sunken);
        }

        .ws-recent-order-number {
            grid-area: number;
            align-self: start;
            color: var(--ws-primary);
            font-family: var(--ws-font-sans);
            font-size: 13px;
            font-weight: 700;
            line-height: 20px;
        }

        .ws-recent-order-body {
            grid-area: body;
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
            font-family: var(--ws-font-serif);
            font-size: 15px;
            font-weight: 700;
            line-height: 21px;
        }

        .ws-recent-order-meta {
            margin-top: 2px;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-serif);
            font-size: 13px;
            line-height: 18px;
        }

        .ws-recent-order-status {
            grid-area: status;
            justify-self: start;
            min-width: 0;
        }

        .ws-dashboard .ws-status-badge {
            max-width: 100%;
            overflow: hidden;
            font-family: var(--ws-font-sans);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .05em;
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        .ws-recent-order-time {
            grid-area: time;
            color: var(--ws-text-muted);
            font-family: var(--ws-font-serif);
            font-size: 12px;
            line-height: 18px;
        }

        .ws-recent-order-chevron {
            grid-area: chevron;
            width: 16px;
            height: 16px;
            color: var(--ws-text-faint);
            stroke-width: 1.8;
            transition: color 140ms ease, transform 140ms ease;
        }

        .ws-recent-order:hover .ws-recent-order-chevron {
            color: var(--ws-primary);
            transform: translateX(2px);
        }

        .ws-quick-actions {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ws-quick-action {
            min-height: 72px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid var(--ws-border);
            border-radius: 16px;
            background: var(--ws-card);
            box-shadow: var(--shadow-sm);
            font-family: var(--ws-font-sans);
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .ws-quick-action:hover {
            border-color: #93C5FD;
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .ws-quick-action-main {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .ws-quick-action-icon {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: var(--ws-sunken);
            color: var(--ws-text-faint);
            transition: background-color 160ms ease, color 160ms ease;
        }

        .ws-quick-action:hover .ws-quick-action-icon {
            background: #EFF6FF;
            color: var(--ws-primary);
        }

        .ws-quick-action-icon svg,
        .ws-quick-action-plus {
            width: 20px;
            height: 20px;
            stroke-width: 1.8;
        }

        .ws-quick-action-label {
            overflow: hidden;
            color: #374151;
            font-size: 14px;
            font-weight: 650;
            line-height: 20px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ws-quick-action-plus {
            flex: 0 0 auto;
            color: #D1D5DB;
        }

        .ws-quick-action:hover .ws-quick-action-plus {
            color: var(--ws-primary);
        }

        .ws-dashboard-kteo .ws-section-header {
            margin-bottom: 12px;
        }

        .ws-dashboard-kteo .ws-list {
            overflow: hidden;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
        }

        .ws-dashboard-kteo .ws-kteo-row {
            min-width: 0;
            padding: 14px 16px;
        }

        .ws-dashboard .ws-empty-state {
            min-height: 132px;
            font-family: var(--ws-font-sans);
        }

        @media (min-width: 1024px) {
            .ws-dashboard .ws-stat-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 20px;
            }

            .ws-dashboard .ws-stat-card {
                min-height: 174px;
                padding: 24px;
            }

            .ws-recent-orders-head {
                min-height: 48px;
                padding: 0 20px;
                display: grid;
                grid-template-columns: 72px minmax(0, 1fr) 176px 80px 16px;
                align-items: center;
                gap: 16px;
                border-bottom: 1px solid var(--ws-border);
                background: var(--ws-page);
                color: var(--ws-text-muted);
                font-family: var(--ws-font-sans);
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .ws-recent-order {
                min-height: 72px;
                padding: 13px 20px;
                grid-template-columns: 72px minmax(0, 1fr) 176px 80px 16px;
                grid-template-areas: "number body status time chevron";
                gap: 16px;
            }

            .ws-recent-order-number {
                align-self: center;
            }

            .ws-recent-order-status {
                width: 100%;
                overflow: hidden;
                justify-self: stretch;
            }

            .ws-recent-order-time {
                white-space: nowrap;
            }
        }

        @media (min-width: 1200px) {
            .ws-dashboard-body {
                grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
                gap: 32px;
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

            .ws-mobile-menu-trigger {
                min-height: 44px;
                padding: 8px 12px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: 0;
                border-radius: 8px;
                background: transparent;
                color: var(--ws-nav-text-hi);
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
            }

            .ws-mobile-menu-trigger:hover {
                background: var(--ws-nav-hover);
            }

            .ws-mobile-menu-trigger svg {
                width: 22px;
                height: 22px;
                stroke-width: 1.8;
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
                border-bottom: 1px solid var(--ws-border);
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
                margin-bottom: 36px;
                gap: 12px;
            }

            .ws-dashboard .ws-stat-card {
                min-height: 136px;
                padding: 16px;
                gap: 12px;
            }

            .ws-stat-icon {
                width: 40px;
                height: 40px;
                flex-basis: 40px;
                border-radius: 10px;
            }

            .ws-stat-icon svg {
                width: 21px;
                height: 21px;
            }

            .ws-dashboard .ws-stat-label {
                font-size: 10px;
                line-height: 14px;
            }

            .ws-dashboard .ws-stat-value {
                font-size: 28px;
                line-height: 32px;
            }

            .ws-dashboard-body {
                gap: 28px;
            }

            .ws-recent-order {
                padding: 14px;
            }

            .ws-quick-action {
                min-height: 68px;
            }

            body.ws-drawer-open {
                overflow: hidden;
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
        openDrawer() {
            this.open = true;
            this.$nextTick(() => this.$refs.drawerClose?.focus());
        },
        closeDrawer() {
            if (! this.open) {
                return;
            }

            this.open = false;
            this.$nextTick(() => this.$refs.menuTrigger?.focus());
        },
    }"
    x-bind:class="{ 'ws-drawer-open': open }"
    x-effect="$refs.mobileHeader.inert = open; $refs.contentRegion.inert = open"
    x-on:keydown.escape.window="closeDrawer()"
    x-on:resize.window="if (window.innerWidth >= 768) open = false"
>
    <header class="ws-mobile-header" x-ref="mobileHeader">
        <button
            type="button"
            class="ws-mobile-menu-trigger"
            x-ref="menuTrigger"
            aria-controls="ws-sidebar"
            aria-expanded="false"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            x-on:click="openDrawer()"
        >
            <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
            </svg>
            Μενού
        </button>

        <a href="{{ route('workshop.dashboard') }}" class="ws-mobile-brand">Garage Manager</a>
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

    @stack('scripts')
</body>
</html>
