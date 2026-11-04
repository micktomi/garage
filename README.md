# Garage Manager

Workshop management for a small vehicle repair business, with a Greek interface for counter staff and the owner.

- **Workshop (`/workshop`)**: Laravel 11 + React 19 + Inertia 2, with a blue design system, reusable record cards, responsive lists and forms, and a status-board dashboard.
- **Administration (`/admin`)**: Filament 3, including owner-only user management.
- **Storage and integrations**: SQLite locally; Laravel database drivers for hosted installations. Gemini powers the assistant and vehicle-registration OCR. Printing uses the existing Blade view.

## Current functionality

Customers, vehicle records and service history, appointments, work orders, parts and stock, KTEO reminders, and work-order printing. Work orders include material lines, labour/parts totals, stale-edit protection and idempotent creation. Owner/staff policies govern destructive operations, purchase-cost access and amendments to completed orders.

Lists share `RecordCard` / `CardGrid`. Tables are retained for comparing parts stock/prices and work-order material lines. The vehicle has a separate read-only detail page and an edit form with its history beside the form on desktop and below it on narrow screens. See [DESIGN.md](DESIGN.md) and [UX-CONTRACT.md](UX-CONTRACT.md) for the maintained UI contracts.

Status transitions are validated in both Workshop and Filament. Completed/cancelled orders are terminal for the normal flow; the owner can reopen a completed order to an open state. These rules and user management are local working-tree changes until committed; inspect `git status` before packaging a release.

## Local setup

Requires PHP 8.2+ with the extensions required by Composer (including SQLite when using SQLite), Composer, and a Node.js runtime compatible with Vite 6.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

For a **new local installation**, create the database and migrate:

```bash
touch database/database.sqlite
php artisan migrate
```

Create the first owner using `php artisan tinker` (choose your own email and strong password):

```php
App\Models\User::create([
    'name' => 'Ιδιοκτήτης',
    'email' => 'owner@example.test',
    'password' => Illuminate\Support\Facades\Hash::make('replace-with-a-strong-password'),
    'role' => App\Enums\UserRole::Owner,
]);
```

Manage subsequent users at `/admin` → `Χρήστες`. Staff cannot access that resource.

Optional: `php artisan db:seed` creates an initial administrator (from `ADMIN_EMAIL` / `ADMIN_PASSWORD`) and seeds the vehicle-model and parts catalogues. When `ADMIN_PASSWORD` is absent, a random password is printed for a newly created administrator. Use seeders deliberately; they are not part of normal operation on an existing customer database.

```bash
npm run build
php artisan serve
```

Sign in at `http://localhost:8000/admin/login`, then open `/workshop` or `/admin`. For frontend development, run `npm run dev` in a second terminal.

## AI assistant and registration scanning

`AI_ASSISTANT_ENABLED` (default `false`) is the only switch. When it is `false` the assistant and the registration scan disappear from both UIs, `/admin/ai-assistant` returns 403, `/workshop/assistant*` and `/workshop/registration-scan*` return 404, and `GeminiClient` refuses to send anything. The legacy `GARAGE_ASSISTANT_ENABLED` is still read as a fallback so existing deployments keep their behaviour.

### What is sent to Google

All calls go through `app/Services/Assistant/GeminiClient.php`: `POST {GEMINI_BASE_URL}/models/{GEMINI_MODEL}:generateContent` (default base URL `https://generativelanguage.googleapis.com/v1beta`), with the key in the `x-goog-api-key` header.

**Assistant** (Filament page and `/workshop/assistant`; at most two requests per message):

- The user's message, the last 12 chat messages (user and assistant text, which can repeat earlier personal data), a fixed system prompt with the current date/time, and the tool definitions.
- If Gemini calls a read tool, its result is sent back in a second request:
  - `find_customer`: customer full name, phone, email, plus the vehicle fields below for each of their vehicles.
  - `find_vehicle`: plate, make, model, year, mileage, VIN, owner full name, owner phone.
  - `get_vehicle_history`: the vehicle fields above, plus up to 25 work orders (date, status, problem description, diagnosis, work performed, mileage, total cost) and up to 25 appointments (date/time, status, description).
  - `list_appointments` (max 31 days, 50 rows): date/time, status, description, customer full name, phone, plate, make and model.
  - `list_open_work_orders` (50 rows): date, status, problem description, customer full name, plate.
- Ambiguous or empty matches and `prepare_*` proposals are answered locally and are **not** sent back.
- Never sent: customer address, notes, AFM, part lines and prices (other than the work order total above), users.

**Registration scan** (`/workshop/registration-scan`; one request per scan):

- The full photo of the registration certificate (base64 JPEG/PNG/WebP, up to 4,096 px on the long edge), so everything printed on it, plus a fixed prompt and the JSON schema.
- Gemini returns: owner name, AFM, phone, address, plate, VIN, make, model, fuel, engine cc and first registration date. The application does not store the image.

Set these in your **local, untracked** `.env`:

```dotenv
AI_ASSISTANT_ENABLED=true
GEMINI_API_KEY=your-key
GEMINI_MODEL=gemini-3.7-flash
```

`config/garage-assistant.php` owns the endpoint and timeouts. Use a model available to your Gemini account that supports image input, structured JSON output and `thinkingLevel: low`. If Laravel configuration is cached, refresh it deliberately after changing environment settings (`php artisan config:clear` locally, or rebuild the configuration cache during your deployment process).

Scanning has four stages: select/capture an image → OCR → human review → explicit save. Recognition does **not** create customers or vehicles. The image is sent to Gemini and is not retained by the application; extracted fields remain in the session until review/save or a new scan. The browser orients the image, retains up to 4,096 pixels on its long edge and uses JPEG quality 0.94 when it fits. Compression/resizing proceeds only as needed to fit the effective PHP upload limit. A local preview of the exact upload can be opened at full size before recognition. Sideways documents can be rotated in 90-degree steps; each preparation starts from the original file to avoid cumulative JPEG degradation. Undecodable formats (for example HEIC on some devices) need conversion to JPEG/PNG/WebP first.

The OCR request uses 4,096 output tokens, shared between thinking and JSON output. A previous 400-token cap could exhaust the budget before producing valid JSON. Ordinary assistant requests keep their separate limit. Partial, blocked or malformed OCR results are rejected rather than saved.

### Scan troubleshooting

- **All devices fail**: inspect `storage/logs` for `registration_extraction`; check that AI is enabled and the key/model are configured. Logs intentionally omit document contents and provider error bodies for OCR.
- **Access/key error (401/403)**: verify the Gemini key, restrictions and account permissions.
- **Model error (404)**: verify model availability for that account.
- **Usage limit (429)**: check quota/billing and retry later. The application's own request throttle is separate.
- **Timeout/unavailable service**: check outbound HTTPS/DNS, provider status and configured timeout.
- **Large camera photos**: PHP may default to `upload_max_filesize=2M`, below the application's 10 MB source-image limit. Compression now respects the effective PHP limit. If needed, set `upload_max_filesize=10M` and `post_max_size=12M` in the configuration of the PHP SAPI actually serving the application, then restart that server. Check the reverse proxy's body-size limit as well. A CLI and PHP-FPM installation can load different ini files.
- **Poor/no recognition**: use a clear, evenly lit document image. Never guess VIN characters; review every field manually.

Automated tests mock Gemini. Live probes must use synthetic documents unless the owner explicitly authorizes sending real records.

## Verification

```bash
php artisan test --compact
npm test
npm run build
git diff --check
```

PHPUnit uses SQLite `:memory:` (see `phpunit.xml`); it does not require resetting the local business database. For browser tests, use an isolated fixture installation/database. The current verification report is [docs/workshop/current-status.md](docs/workshop/current-status.md). The [September migration report](docs/workshop/verification.md) is historical and is not evidence for later changes.

## Production and repository hygiene

- Set `APP_ENV=production`, `APP_DEBUG=false` and the correct `APP_URL`. `ProductionConfigGuard` refuses production startup with debug enabled.
- Configure the database for the deployment and expected concurrency; use migrations deliberately during deployment, not as a troubleshooting reset.
- Decide whether AI/OCR should be enabled and configure its key, model, outbound access and upload limits.
- Keep an off-machine backup. For a live SQLite database, use its backup command rather than copying a file mid-write:

```bash
sqlite3 database/database.sqlite ".backup 'storage/backups/garage-backup.sqlite'"
```

Create `storage/backups` first. That directory is ignored; arrange a separate retention/off-machine copy policy.

Do not commit `.env`, databases, logs, credentials, `vendor`, `node_modules` or generated runtime/build assets. Generated browser screenshots under `docs/workshop/screenshots/` are intentionally ignored; verification summaries are tracked. No deployment is implied by a passing build or by this documentation.
