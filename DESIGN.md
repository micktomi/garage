---
version: alpha
name: Garage Manager Workshop
description: Greek workspace for the service counter, organised around the status board of vehicles in the workshop.
colors:
  primary: '#2563eb'
  primary-soft: '#dbeafe'
  background: '#f4f8ff'
  surface-muted: '#ebf2fd'
  surface: '#ffffff'
  text: '#0f172a'
  muted: '#475569'
  faint: '#5b6b82'
  border: '#dbe5f3'
  info: '#3556c8'
  warning: '#8f5500'
  parts: '#b03a07'
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
    height: '38px'
  input:
    height: '40px'
  table:
    height: '58px'
---

# Garage Manager Workshop

## Overview
The service counter is the reference: answer a customer call, locate a vehicle,
and progress its work without losing the queue. The home screen is a status
board: every open work order is a ticket in the lane of its status (new, in
progress, awaiting parts, ready for pickup), oldest first. The licence plate is
the identifier and always comes first. Colour means status and nothing else.
Product register, Greek copy, desktop and phone. Avoid marketing heroes and
decorative cards. Filament is a separate surface and keeps its own appearance
until it is migrated.

Runtime token owner: `resources/css/workshop.css`, scoped under `.workshop-app`.
This document mirrors those canonical tokens; neither generates Filament tokens.

## Colors
Bright blue identity on a cool blue-white workspace (`--page`), white surfaces,
slate text. `--primary` (#2563eb) is the primary button, brand mark, active
destination, links and focus; `--primary-soft` (#dbeafe) marks the active nav item
and own chat messages. Five status tones (info, warning, parts, success, danger)
each have a soft background and a foreground that meets AA on it. Success is
teal: `--success` (#0f766e) for text, `--success-accent` (#0d9488) only for dots
and row accents, because white-on-#0d9488 text is 3.7:1. Awaiting parts has its
own tone; a completed order uses the quiet `calm` tone. Danger marks only
actionable risk: expired KTEO and orders in the shop for five days or more.
No dark theme.

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
Borders and spacing carry hierarchy; shadow only on dialogs and the mobile sheet.
Lanes use a muted fill, tickets a white surface with a 1px border.

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
