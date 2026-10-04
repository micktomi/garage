---
version: alpha
name: Garage Manager Workshop
description: Greek desktop workspace for the service counter and workshop queue.
colors:
  primary: '#3659d9'
  background: '#edf1f6'
  surface-muted: '#f3f6fa'
  surface: '#ffffff'
  text: '#182230'
  muted: '#596779'
  border: '#ccd5e1'
  danger: '#a32d3b'
  success: '#246848'
  warning: '#885513'
  info: '#305aaa'
typography:
  sans:
    fontFamily: 'Segoe UI, system-ui, sans-serif'
  mono:
    fontFamily: 'ui-monospace, monospace'
rounded:
  DEFAULT: '0.5rem'
spacing:
  sidebar: '236px'
  page: '28px'
components:
  button:
    height: '38px'
  input:
    height: '40px'
  table:
    height: '52px'
---

# Garage Manager Workshop

## Overview
The service counter is the reference: answer a customer call, locate a vehicle,
and progress its work without losing the queue. Product register, Greek copy,
desktop first. The distinctive identifier is the compact monospace licence plate;
quiet surfaces keep attention on overdue work and pickup status. Avoid marketing
heroes, decorative cards and the former dark/amber Blade theme. Filament is a
separate surface and its appearance is outside this contract.

Runtime token owner: `resources/css/workshop.css`, scoped under `.workshop-app`.
This document mirrors those canonical tokens; neither generates Filament tokens.

## Colors
Cool grey workspace, white panels, lightly shaded panel/table headers and form
actions, dark text, one blue primary accent. Status colors
have textual labels from the server. Neutral metrics; danger only for actionable
risk, not decoration. No dark theme in this migration.

## Typography
Segoe UI/system fonts cover Greek without a remote font dependency. Page title
26px, body/control 14px, metadata 13px, numeric metrics 28px. Sentence case,
Greek accents retained, tabular numeric values and monospace plates/VIN.

## Layout
236px fixed sidebar on desktop; main uses the remaining viewport. One document
scroll owner, natural-height forms, horizontal overflow owned by table wrappers.
Forms may have an internal 1080px maximum. Below 900px navigation becomes a
labelled collapsible region in document flow; every destination remains reachable.
28px workspace padding, 16px narrow padding. No global content-width cap.

## Elevation & Depth
Borders and spacing carry hierarchy. Subtle shadow only on panels/dialogs;
no gradients, decorative glow, or full-card status backgrounds. Panel borders
and a faint shadow separate the white cards from the workspace.

Token path: `workshop.css` owns `--page`, `--surface`, `--surface-muted`,
`--border` and the semantic tone foreground/background/border variables; shared
Panel, DataTable, Form and Feedback components consume them through their CSS
classes. `colors.background`, `colors.surface`, `colors.surface-muted` and
`colors.border` above mirror those runtime values. Filament has no adapter.

## Shapes
8px controls/panels; restrained pill status badges. Thin plate border indicates
an identifier, not an action. Table links provide navigation.

## Components
Shared UI owners live in `resources/js/workshop/Components`. Buttons expose
busy/disabled/focus states without changing width. Native select/date/time and
datalist popups deliberately remain browser-owned; Greek labels and formatted
values are application-owned. Forms preserve inputs and show server errors.
Dialogs use native `dialog.showModal()` with accessible titles, Escape and focus
restoration. Tables use native semantics, pagination and URL filters. Motion is
limited to short state changes; reduced-motion preference disables animation.

## Do's and Don'ts
- Keep primary identifiers and next actions easy to scan.
- Share form, feedback, status and table behavior across pages.
- Do not add technician assignment, quotes, customer messaging or business rules.
- Do not import Workshop CSS into Filament.
