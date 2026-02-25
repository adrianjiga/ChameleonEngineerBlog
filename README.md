# Chameleon Engineer Blog

A full-featured blog platform built with Laravel 12, Inertia.js v2, and Vue 3.

## Tech Stack

- **Backend**: Laravel 12, Laravel Fortify (headless auth), Laravel Wayfinder
- **Frontend**: Vue 3, Inertia.js v2, Tailwind CSS v4, Reka UI
- **Editor**: TipTap v2 rich text editor
- **Storage**: Local (dev) / AWS S3 (prod) via Flysystem
- **Email**: Resend
- **Database**: SQLite (dev)

## Getting Started

```bash
# First-time setup (install deps, generate key, migrate, build assets)
composer run setup

# Start all dev services (PHP server, queue, Pail logs, Vite)
composer run dev
```

## Commands

```bash
# Run all tests with Pint lint check
composer run test

# Format PHP
vendor/bin/pint --dirty --format agent

# Format JS/TS/Vue
npm run format

# Lint JS/TS/Vue
npm run lint

# Build frontend
npm run build

# Run tests
php artisan test --compact
```

## Implementation Progress

See [PLAN.md](PLAN.md) for the full implementation roadmap.

| Phase | Description | Status |
|-------|-------------|--------|
| 1a | Composer + NPM dependencies | ✅ Done |
| 1b | PostStatus enum | Pending |
| 1c | Database migrations | Pending |
| 2 | Models, Policies, Factories, Seeders | Pending |
| 3 | Image Service, Observer, Console Commands | Pending |
| 4 | Form Requests, Controllers, Routes, Blade Views | Pending |
| 5 | Feature Tests | Pending |
| 6 | Frontend UI Primitives | Pending |
| 7 | Application Components, Layout, Feature Pages | Pending |
| 8 | Seeder Completion & Final Validation | Pending |
