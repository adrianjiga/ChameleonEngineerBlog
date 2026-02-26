# Chameleon Engineer Blog

A full-featured blog platform built with Laravel 12, Inertia.js v2, and Vue 3.

## Tech Stack

- **Backend**: Laravel 12, Laravel Fortify (headless auth), Laravel Wayfinder
- **Frontend**: Vue 3, Inertia.js v2, Tailwind CSS v4, Reka UI
- **Editor**: TipTap v3 rich text editor
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

## Features

| Area | Details |
|------|---------|
| Authentication | Login, register, password reset, email verification, 2FA (via Laravel Fortify) |
| Blog | Public post listing with search & category filter, paginated, sanitized HTML rendering |
| Posts | Full CRUD, rich text editor (TipTap), cover image upload, SEO fields, autosave, scheduled publishing |
| Categories | Admin-only create/update/delete; any authenticated user can view |
| Dashboard | Stats overview, recent posts, popular categories |
| Image handling | Upload optimisation + variant generation (large/medium/thumb) via Intervention Image; S3-compatible storage |
| Console commands | `posts:publish-scheduled`, `posts:cleanup-images` |
| Sitemap & RSS | `/sitemap.xml`, `/feed.rss` |
| Authorization | `PostPolicy`, `CategoryPolicy` — admin and owner-based gates |
