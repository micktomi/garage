# Workshop UX contract

## Product context
Greek operational workspace for garage staff; Europe/Athens timezone, Gregorian
calendar, el-GR dates/currency, WCAG 2.2 AA target. User's current migration brief
is the architecture authority. Business authorities remain existing Actions,
Models, Policies and WorkOrderStatus. No delete/catalogue administration flows
are introduced. Filament keeps administration and the existing authentication.

## Canonical UI Map
| Capability | Owner | Source | Variants | Verification |
|---|---|---|---|---|
| Select/Listbox | Field | Native browser | select / datalist | browser keyboard/popup |
| Date | Field | Native browser | date / time | browser and server date tests |
| Form | Form + Field | Inertia useForm/server validation | create / edit / review | Feature + browser |
| Scrollbar | workshop.css | DESIGN.md | table horizontal overflow | computed styles |
| Feedback | Feedback | shared flash/errors | success / error / offline | browser + Inertia props |
| CRUD | Workshop routes/controllers | existing actions/models/policies | owning detail or list | Feature + browser |

## Dataset navigation
20 rows/server page; q/status/history/page persisted in query string. Explicit
Enter/search applies filters; clear applies immediately. No automatic remote
search means no debounce race. Native table with horizontal overflow on narrow
screens. No bulk selection. Empty/no-results have text and an actionable next step.

## Flow ledger
Create customer -> customer detail -> add vehicle. Create vehicle -> vehicle edit
and history. Create/edit work order -> work order detail. Create/edit appointment
-> appointment list. OCR -> review -> existing registration action -> vehicle edit.
AI -> proposal -> explicit confirmation -> shared executor; cancellation discards
the shared token. Mutations wait for server confirmation; busy disables duplicates.

## Navigation and responsive behavior
Page Head uses Greek title + Garage Manager. Fixed sidebar desktop, labelled
collapsible narrow navigation. Tables scroll horizontally, long forms vertically.
Print remains existing Blade in a separate browser tab. Error pages show a Greek explanation and a safe return to the workshop.

## Validation and resilience
Server rules own business validation; noValidate avoids browser bubbles. Field
errors use aria-invalid/describedby; shared summary exposes all errors including
lock_version. First invalid field receives focus. Stale work orders retain entered
values, show the server conflict, and offer reload. No force-overwrite flow.
Unsaved navigation requires an app dialog; actual tab unload uses beforeunload.
Network failures preserve forms and offer retry; no queued or optimistic writes.
No PII persisted in browser localStorage. AI history is session-scoped by user.

## Permission and feedback
Capabilities are supplied server-side and enforced again on writes. Hide completed
order editing/status actions for Staff; hide purchase-cost inputs without pricing
ability. SMS/call actions open local apps, no server dispatch. Clipboard copy has
visible success/error and selectable text fallback. Dialogs are native modal dialog,
Cancel initially focused; Escape closes and focus returns to trigger.

## Migration gates
Existing staged index is preserved byte-for-byte. Keep old Blade files until
React parity/browser verification passes. The temporary preview switch was removed at cutover. No push, commit or deployment.

## Verification
Feature suite includes Filament and domain regressions. Build with existing Vite.
Browser: dashboard, work orders list/detail/create/edit, make/model create/edit,
OCR review, assistant, narrow viewport, validation, stale conflict and permissions.
External Gemini is mocked in tests; no live requests during normal verification.
