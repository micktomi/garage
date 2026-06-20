# Garage Manager

Garage Manager is a Laravel + Filament application for managing a small vehicle workshop.

It provides an admin panel for customers, vehicles, work orders, spare parts, consumables, dashboard widgets, and basic workshop cost tracking.

## Features

- Customer management
- Vehicle management
- Work orders
- Work order statuses
- Spare parts / consumables
- Work order part lines with quantity, unit price and line total
- Labor cost + parts cost calculation
- Total work order cost calculation
- Stock adjustment when parts are used
- Dashboard widgets
- Vehicle quick search
- KTEO reminder widget
- Print view for work orders
- Demo seed data
- Feature tests for work order cost calculation

## Tech Stack

- Laravel
- Filament Admin Panel
- SQLite for local/demo usage
- Vite
- PHPUnit / Laravel test runner

## Local Installation

Clone the repository:

```bash
git clone <repository-url>
cd garage-manager
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Create local SQLite database:

```bash
touch database/database.sqlite
```

Run migrations and seed demo data:

```bash
php artisan migrate --seed
```

Build frontend assets:

```bash
npm run build
```

Start local server:

```bash
php artisan serve
```

Then open:

```text
http://localhost:8000/admin
```

## Tests

Run all tests:

```bash
php artisan test
```

## Demo Data

The project includes seeders for demo usage.

Demo data may include example customers, vehicles, spare parts, consumables and work orders.

For a real production/customer installation, do not use demo seeders unless you explicitly want sample data.

## Production Notes

Before production deployment:

- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Set the correct `APP_URL`
- Generate a production `APP_KEY`
- Configure the production database
- Run migrations with:

```bash
php artisan migrate --force
```

- Create the admin user
- Do not commit `.env`
- Do not commit local SQLite databases
- Do not commit logs, cache files, `vendor`, or `node_modules`

## Repository Hygiene

This repository should contain source code only.

Do not commit:

- `.env`
- `vendor/`
- `node_modules/`
- `database/*.sqlite`
- `storage/logs/*.log`
- `storage/framework/*`
- `bootstrap/cache/*.php`
- generated build/runtime artifacts

## Status

Current status: demo-ready Laravel / Filament workshop management application.

The project is suitable for local demonstration and evaluation by a small workshop.