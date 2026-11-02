---
version: alpha
name: Garage Manager Workshop
description: Greek workspace for the service counter, organised around the status board of vehicles in the workshop.
colors:
  primary: '#2563eb'
  primary-soft: '#e5efff'
  background: '#f4f7fc'
  surface-muted: '#f1f5fb'
  surface: '#ffffff'
  surface-subtle: '#f8faff'
  navigation: '#122a50'
  navigation-text: '#d8e5fa'
  navigation-muted: '#a8bfdf'
  navigation-focus: '#a9ceff'
  border-strong: '#7b8da6'
  warning-header: '#f3e4bb'
  parts-header: '#ede0c2'
  text: '#0f172a'
  muted: '#475569'
  faint: '#5b6b82'
  border: '#dce4ef'
  info: '#1d4ed8'
  warning: '#805b12'
  parts: '#78602b'
  success: '#0f766e'
  success-accent: '#0d9488'
  danger: '#b3261e'
typography:
  display:
    fontFamily: 'Geologica, IBM Plex Sans, system-ui, sans-serif'
  sans:
    fontFamily: 'IBM Plex Sans, system-ui, Segoe UI, sans-serif'
  mono:
    fontFamily: 'JetBrains Mono, ui-monospace, monospace'
rounded:
  sm: '6px'
  md: '10px'
  lg: '14px'
spacing:
  sidebar: '236px'
  page: '32px'
components:
  button:
    height: '40px'
    backgroundColor: '{colors.primary}'
    color: '{colors.surface}'
  input:
    height: '40px'
    backgroundColor: '{colors.surface}'
    color: '{colors.text}'
    borderColor: '{colors.border-strong}'
    placeholderColor: '{colors.faint}'
  table:
    height: '58px'
    headerBackground: '{colors.surface-muted}'
    color: '{colors.muted}'
  navigation:
    backgroundColor: '{colors.navigation}'
    color: '{colors.navigation-text}'
    mutedColor: '{colors.navigation-muted}'
    focusColor: '{colors.navigation-focus}'
  panel:
    backgroundColor: '{colors.surface}'
    headerBackground: '{colors.primary-soft}'
    borderColor: '{colors.border}'
  record-card:
    backgroundColor: '{colors.surface}'
    footerBackground: '{colors.surface-subtle}'
  status-badge:
    infoColor: '{colors.info}'
    warningColor: '{colors.warning}'
    partsColor: '{colors.parts}'
    successColor: '{colors.success}'
    dangerColor: '{colors.danger}'
    successAccent: '{colors.success-accent}'
  status-board:
    warningHeader: '{colors.warning-header}'
    partsHeader: '{colors.parts-header}'

---

# Garage Manager Workshop

## Overview
The service counter is the reference: answer a customer call, locate a vehicle,
and progress its work without losing the queue. The home screen is a status
board: every open work order is a ticket in the lane of its status (new, in
progress, awaiting parts, ready for pickup), oldest first. The licence plate is
the identifier and always comes first. Navy and blue identify the workspace and actions; semantic colour identifies status.
Product register, Greek copy, desktop and phone. Avoid marketing heroes and
decorative cards. Filament is a separate surface and keeps its own appearance
until it is migrated.

Runtime token owner: `resources/css/workshop.css`, scoped under `.workshop-app`.
This document mirrors those canonical tokens; neither generates Filament tokens.

## Colors
The approved November 2026 identity uses a deep navy navigation surface
(`#122a50`), bright blue actions (`--primary`, `#2563eb`), white cards and a cool
blue-white workspace (`--page`, `#f4f7fc`). This is a light content theme with navy
navigation, not a second/dark theme. The existing Garage Manager name and car icon
remain. `--nav-*` owns navigation text, icons, hover, active and focus colours across
the desktop rail, phone menu and bottom tabs. Active destinations have a solid blue
fill with white text; phone tabs retain their existing geometry and bold active label.

`--primary-soft` (`#e5efff`) provides shared panel headings and own chat messages.
Links use `--link` -> `--primary-strong` (`#1d4ed8`) for readable text on tinted
surfaces. Inputs and secondary controls use `--border-strong` (`#7b8da6`), distinct
from the lighter grouping border (`--border`, `#dce4ef`). White cards have a very
subtle shadow and quieter `--surface-subtle` footers.

Status meanings and labels are unchanged. New work is blue; in-progress work and
awaiting parts use two restrained amber tones with dark text, never saturated
orange. Their header fills are `--warning-header` (`#f3e4bb`) and `--parts-header`
(`#ede0c2`). Ready work is teal green (`--success`, `#0f766e`). The soft badge
backgrounds and readable foregrounds come from the same status tokens. Counts on
solid headers/selected chips have white backgrounds and dark text to preserve AA.
Completed orders use `calm`. Red remains actionable risk: expired KTEO, orders in
the shop for five days or more, errors and destructive actions. Success accents
(`#0d9488`) are graphical only, not small text on white.

Runtime mapping (existing CSS is the canonical owner; this document mirrors it):

| Role | Runtime tokens | Shared consumers |
|---|---|---|
| Navigation | `--nav-*`, `--primary`, `--on-primary` | WorkshopLayout rail, mobile menu/topbar/tabbar |
| Surfaces/depth | `--page`, `--surface*`, `--border*`, `--panel-shadow`, `--ticket-shadow`, `--hover-shadow` | Panel, RecordCard, tickets, forms, tables, tools |
| Action/focus | `--primary*`, `--link`, `--primary-shadow`, `--nav-focus` | Button, links, search, Field, keyboard focus |
| Semantic status | `--info-*`, `--warning-*`, `--parts-*`, `--success-*`, `--danger-*`, `--accent-*` | Badge, StatusBoard lanes, card accents, feedback |

Status accents are applied by shared card/ticket selectors to existing badges;
entities do not supply a second visual implementation. Every titled Panel shares
one blue-tinted header. Amber is used only for real warnings/waiting states,
not as a decorative panel colour. Filament is not a consumer of these tokens.

## Typography
Self-hosted WOFF2 (`public/fonts`, SIL OFL): Geologica for titles and numerals,
IBM Plex Sans for text and forms, JetBrains Mono for plates and identifiers. All
three include the Greek subset. One scale: page title 28/600, section title 17/600,
card and ticket title 15/600, body and controls 14, metadata 12.5, labels 11
uppercase with tracking, glance numerals 30. Tabular numerals for money, dates and
counts. Work orders are always written `#0003` (helper `padId`), in mono.

## Layout
236px sidebar on desktop, grouped as daily work, reminders and stock, tools.
Sticky topbar with search and the two primary actions. Below 900px the sidebar
becomes a bottom sheet opened from the "Menu" tab, and a fixed bottom tab bar
(Home, Orders, New, Appointments, Menu) provides navigation. Page padding 32px
(16px narrow); section gap 24px; card gap 16px; spacing scale 4/8/12/16/24/32.
Every page fills the same content column; Detail and Form pages share one 2fr/1fr
frame (`ws-columns`) so right edges align. The aside stacks below the main column
under 1150px. The AI chat and the registration scan are tools: they keep their own
physical layout in a 960px column (`ws-chat`, `ws-tool`).

## Templates
One template per page type; entities only change the content.
- **Dashboard.** Date + title + glance counters, status board (four lanes; chips and a
  filtered list on phones), agenda and KTEO panels.
- **List.** `PageHeading` (one primary button) -> `FilterBar` -> `CardGrid` of
  `RecordCard` -> pager. Two columns when the list is at least 640px wide, one
  otherwise (container query). Entity cards: `WorkOrderCard`, `VehicleCard`,
  `CustomerCard`, `AppointmentCard`, `KteoCard`. Search results group cards under
  section titles.
- **Detail.** `PageHeading` -> `ws-columns`: main stack of panels and card sections,
  aside with the related entity (`RecordCard`) and actions. `DetailList` for facts.
- **Form.** `PageHeading` (no primary button) -> feedback summary -> form panel with
  titled sections and a footer holding Cancel and the single primary button. A
  vehicle form adds its history as an aside (below the form on phones).
- **Error.** Centered card outside the shell.

## Card anatomy
Header: [plate] [title + subtitle] [badge]. Body: lead text (2 lines), person,
facts. Footer (min 54px): metadata on the left, icon buttons (call, SMS, copy), then
**one** small primary button with a verb. The whole card is clickable through the
title link; inner links and buttons sit above it. A card never shows a field the
controller does not already send.

## Controls and states
Fields, selects, dates, search and default buttons are 40px; small buttons and icon
buttons are 32px. One `Badge` (24px, pill, six tones) renders statuses, KTEO
deadlines (`kteoStatus`) and stock warnings. One `FilterBar`: optional search, then
page controls. One `EmptyState`: what is missing, what to do, optional action. The
pager appears only when there is more than one page; otherwise a count line
("8 εντολές") sits above the list. No inline styles; spacing helpers `ws-mt-*`.

## Tables
A table is allowed only to compare numbers per column across homogeneous rows with
no per-row actions: the material lines of a work order and the parts catalogue.
Every other list is a card list. `DataTable` is the single table component.

## Elevation & Depth
Borders and spacing carry hierarchy. Panels and cards use the shared low-opacity
navy shadow; hover deepens it without moving the content. Dialogs and the mobile
sheet retain stronger elevation. Status lanes use semantic tinted fills and coloured
headers; tickets retain white surfaces with a 1px border. No gradients or decorative
widgets are introduced.

## Shapes
6px controls, 10px tickets, 14px panels. Plates have a blue EU strip and a dark
border; they identify and never act as buttons.

## Components
Shared UI owners live in `resources/js/workshop/Components`: `ui.jsx` (primitives:
Button, Panel, Field, Form, FilterBar, Badge, StatusBadge, Plate, RecordCard,
CardGrid, DetailList, EmptyState, DataTable) and `cards.jsx` (entity cards, status
board). Buttons expose
busy/disabled/focus states without changing width. Native select/date/time and
datalist popups deliberately remain browser-owned; Greek labels and formatted
values are application-owned. Forms preserve inputs and show server errors.
Dialogs use native `dialog.showModal()` with accessible titles, Escape and focus
restoration. Pagination and filters live in the URL. Motion is
limited to short state changes; reduced-motion preference disables animation.

## Do's and Don'ts
- Keep primary identifiers and next actions easy to scan.
- Share form, feedback, status and card behavior across pages; no page-specific lists.
- Do not add technician assignment, quotes, customer messaging or business rules.
- Do not import Workshop CSS into Filament.
