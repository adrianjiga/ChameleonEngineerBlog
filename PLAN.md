# Implementation Plan: Replicate example-app-1 into ChameleonEngineerBlog

## Context

`example-app-1` is a complete Laravel 12 + Inertia v2 + Vue 3 blog platform. `ChameleonEngineerBlog` is the same tech stack but currently only has authentication/settings scaffolding. This plan replicates the blog platform feature-by-feature in 8 logically-ordered phases, each independently deployable with all tests passing.

---

## What's Being Added (Gap Summary)

| Area | New Items |
|------|-----------|
| Composer deps | `intervention/image`, `league/flysystem-aws-s3-v3`, `resend/resend-laravel` |
| NPM deps | `@tiptap/vue-3` + extensions, `dompurify`, `vue-sonner` |
| Migrations | `is_admin` on users, `categories`, `posts`, `category_post`, SEO fields on posts |
| Enum | `PostStatus` (Draft/Published/Unpublished) |
| Models | `Post`, `Category`; update `User` |
| Policies | `PostPolicy`, `CategoryPolicy` |
| Service | `ImageOptimizer` + `config/images.php` |
| Observer | `PostObserver` (cache invalidation) |
| Console Commands | `posts:publish-scheduled`, `posts:cleanup-images` |
| Controllers | `DashboardController` (update), `BlogController`, `PostController`, `CategoryController`, `SitemapController`, `RssFeedController` |
| Form Requests | `StorePostRequest`, `UpdatePostRequest`, `StoreCategoryRequest`, `UpdateCategoryRequest` |
| Middleware | `HandleInertiaRequests` (add flash props) |
| Bootstrap | `bootstrap/app.php` (Inertia error handling) |
| Blade Views | `resources/views/sitemap/index.blade.php`, `resources/views/feed/rss.blade.php` |
| Factories/Seeders | `PostFactory`, `CategoryFactory`, update `UserFactory` + `DatabaseSeeder` |
| Frontend types | `blog.ts` types, add `is_admin` to User type |
| Frontend composable | `useSanitizedHtml.ts` |
| Frontend UI primitives | `select/`, `table/`, `tabs/`, `popover/`, `sonner/` components |
| Frontend app components | `RichTextEditor.vue`, `CoverImageUpload.vue` |
| Frontend layout updates | `AppSidebar.vue` (nav items), `AppSidebarLayout.vue` (Sonner + flash watcher) |
| Frontend pages | `ErrorPage.vue`, `Dashboard.vue` (update), `blog/Index.vue`, `blog/Show.vue`, `posts/Index.vue`, `posts/Create.vue`, `posts/Edit.vue`, `posts/Show.vue`, `categories/Index.vue` |
| Tests | `Posts/PostControllerTest`, `Categories/CategoryControllerTest`, `Blog/BlogControllerTest`, update `DashboardTest` |

---

## Phase 1: Dependencies, Database Schema, and Core Enum

**Goal:** Install all new packages and create the full database schema.

### Actions
1. ~~Install Composer deps~~ ✅ `intervention/image:^3.11`, `league/flysystem-aws-s3-v3:^3.0`, `resend/resend-laravel:^1.0` (note: ^0.3 incompatible with Laravel 12; bumped to ^1.0)
2. ~~Install NPM deps~~ ✅ `@tiptap/vue-3`, `@tiptap/starter-kit`, `@tiptap/extension-image`, `@tiptap/extension-link`, `@tiptap/extension-placeholder`, `@tiptap/extension-character-count`, `dompurify`, `vue-sonner`; `@types/dompurify` as devDep
3. ~~Create `app/Enums/PostStatus.php`~~ ✅ backed string enum: `Draft='draft'`, `Published='published'`, `Unpublished='unpublished'`; methods `label(): string` and `color(): string`
4. ~~Create migration: `add_is_admin_to_users_table`~~ ✅ `boolean('is_admin')->default(false)->after('password')`
5. ~~Create migration: `create_categories_table`~~ ✅ `id, name, slug (unique), description (nullable text), timestamps`
6. ~~Create migration: `create_posts_table`~~ ✅ `id, user_id (FK cascade), title, slug (unique), excerpt (nullable), content (longText), featured_image (nullable), status (string default 'draft'), published_at (nullable), timestamps`
7. ~~Create migration: `create_category_post_table`~~ ✅ junction table with `category_id + post_id` FKs, unique constraint on both
8. ~~Create migration: `add_seo_fields_to_posts_table`~~ ✅ `meta_title (nullable), meta_description (nullable text), scheduled_at (nullable datetime)`
9. ~~Create `config/images.php`~~ ✅ `sizes: [large:1200, medium:800, thumb:400], quality: 80, format: webp`
10. ~~Run `php artisan migrate`~~ ✅ all 5 migrations ran successfully

**Gotchas:**
- Migrations created in same minute share timestamps — manually rename files to add sequential numeric suffixes to guarantee ordering
- `app/Enums/` is a standard Laravel sub-directory under `app/` — no new base folder approval needed

**Verify:** `php artisan migrate:status` shows all migrations as `Ran`; `php artisan test --compact` all green

---

## Phase 2: Models, Policies, Factories, and Seeders

**Goal:** Complete Eloquent domain model layer with authorization and test data.

### Files
- ~~`app/Models/User.php`~~ ✅ added `is_admin` to `$fillable`, boolean cast, `posts(): HasMany<Post>`, `isAdmin(): bool`; also added `'is_admin' => false` to `UserFactory` default (needed for model tests)
- ~~`app/Models/Post.php`~~ ✅ full model with `PostStatus` cast, relationships, scopes (`published`, `scheduled`, `readyToPublish`, `forUser`, `search`), `reading_time` and `featured_image_urls` accessors, `booted()` for auto-slug; `getRouteKeyName()` returns `'slug'`
- ~~`app/Models/Category.php`~~ ✅ `name, slug, description` fillable, `posts(): BelongsToMany<Post>`, `booted()` for auto-slug
- ~~`app/Policies/PostPolicy.php`~~ ✅ `viewAny/create` → true; `view/update/delete/restore` → admin OR owner; `forceDelete` → admin only
- ~~`app/Policies/CategoryPolicy.php`~~ ✅ read methods → true; write methods → admin only
- ~~`database/factories/UserFactory.php`~~ ✅ `'is_admin' => false` default added earlier; `admin()` state added
- ~~`database/factories/PostFactory.php`~~ ✅ default is draft; states: `published()`, `draft()`, `scheduled()`, `withFeaturedImage()`; slug omitted from factory (model booted() handles it)
- ~~`database/factories/CategoryFactory.php`~~ ✅ name + optional description; slug omitted from factory (model booted() handles it)
- ~~`database/seeders/DatabaseSeeder.php`~~ ✅ admin user + 3 users + 7 categories + 12 published posts + 3 drafts + 2 scheduled; categories attached to published posts

**Gotchas:**
- Policies are auto-discovered in Laravel 12 — no registration needed
- `Post`'s `featured_image_urls` accessor must guard `if ($this->featured_image === null) return []` — the ImageOptimizer doesn't exist yet
- Create `Post.php` before updating `User.php` (to avoid unresolved import)

**Verify:** `php artisan test --compact` all green

---

## Phase 3: Image Service, Observer, Console Commands, and AppServiceProvider

**Goal:** All PHP infrastructure layer complete.

### Files
- ~~`app/Services/ImageOptimizer.php`~~ ✅ (new) — constructor injects `FilesystemManager`; methods: `optimize(UploadedFile, string): string`, `generateVariants(string, ?string): array` (reads from `config('images.*')`), `deleteWithVariants(string): void`, `getVariantUrls(string): array`; uses Intervention Image v3 GD driver
- ~~`app/Observers/PostObserver.php`~~ ✅ (new) — `saved()` and `deleted()` call `Cache::forget("post:{$post->id}")` and `Cache::increment('blog:index:version')`; `deleted()` also calls `ImageOptimizer::deleteWithVariants()` wrapped in try/catch
- ~~`app/Providers/AppServiceProvider.php`~~ ✅ — update `register()` to bind `ImageOptimizer` as singleton; update `boot()` to register `PostObserver`, use `CarbonImmutable`, prohibit destructive commands in production, set production password rules (min 12, mixed case, numbers, symbols, uncompromised)
- ~~`app/Console/Commands/PublishScheduledPosts.php`~~ ✅ (new) — signature `posts:publish-scheduled`; query `Post::readyToPublish()->get()`, update each to Published with `published_at = now()`
- ~~`app/Console/Commands/CleanupOrphanedImages.php`~~ ✅ (new) — signature `posts:cleanup-images {--dry-run}`; scan `Storage::disk()->allFiles('posts')`, diff against DB `featured_image` values, delete orphans
- ~~`routes/console.php`~~ ✅ — schedule `posts:publish-scheduled` everyMinute, `posts:cleanup-images` weekly

**Gotchas:**
- Intervention Image v3 uses `ImageManager::gd()` static factory — not `new ImageManager(['driver' => 'gd'])`
- Wire `Post` model's `deleting` event in `booted()` to call `app(ImageOptimizer::class)->deleteWithVariants()` — now that the service exists

**Verify:** `php artisan list | grep posts` shows both commands; `php artisan test --compact` all green; run Pint `vendor/bin/pint --dirty --format agent`

---

## Phase 4: Form Requests, Controllers, Routes, and Blade Views

**Goal:** Complete server-side HTTP layer — validation, controllers, routes, Blade views, middleware updates.

### Files
- ~~`app/Http/Requests/Posts/StorePostRequest.php`~~ ✅ `title (required), content (required), excerpt (nullable max:500), status (Rule::enum(PostStatus)), featured_image (nullable file image max:5120), category_ids (nullable array of existing IDs), meta_title/description (nullable), scheduled_at (nullable date after:now), published_at (nullable date)`
- ~~`app/Http/Requests/Posts/UpdatePostRequest.php`~~ ✅ identical rules to Store but `scheduled_at` uses `after_or_equal:now`
- ~~`app/Http/Requests/Categories/StoreCategoryRequest.php`~~ ✅ `name (required unique:categories.name), description (nullable)`
- ~~`app/Http/Requests/Categories/UpdateCategoryRequest.php`~~ ✅ same with `Rule::unique()->ignore($this->route('category'))`
- ~~`app/Http/Controllers/DashboardController.php`~~ ✅ (new, invokable) — counts (total/published/draft posts, total categories), recent 5 posts (scoped to user or all for admin), popular 5 categories; returns `Inertia::render('Dashboard', [...])`
- ~~`app/Http/Controllers/BlogController.php`~~ ✅ (new) — `index()` paginated published posts (15/page) with search/category filter + 5-min cache using version key; `show(Post $post)` checks published status (abort 404 if not), 10-min cache, 3 related posts
- ~~`app/Http/Controllers/PostController.php`~~ ✅ (new, resource) — constructor injects `ImageOptimizer`; full CRUD + `autosave()` (JSON response) + `uploadImage()` (JSON response); policy gates via `$this->authorize()`
- ~~`app/Http/Controllers/CategoryController.php`~~ ✅ (new) — index (withCount posts + can flags), store, update, destroy with policy gates
- ~~`app/Http/Controllers/SitemapController.php`~~ ✅ (new, invokable) — returns Blade view with `Content-Type: application/xml`
- ~~`app/Http/Controllers/RssFeedController.php`~~ ✅ (new, invokable) — returns Blade view with `Content-Type: application/rss+xml`
- ~~`app/Http/Middleware/HandleInertiaRequests.php`~~ ✅ — add to shared array: `'flash' => ['success' => fn() => session('flash.success'), 'error' => fn() => session('flash.error')]`
- ~~`bootstrap/app.php`~~ ✅ — add `withExceptions()` handler: map 404/403/500/503 to `Inertia::render('ErrorPage', ['status' => $status])`; map 419 to `back()->with('flash.error', '...')`
- ~~`routes/web.php`~~ ✅ — replace static `Inertia::render('Dashboard')` with `DashboardController::class`; add blog/sitemap/feed routes; add `Route::resource('posts', ...)` + autosave + uploadImage routes; add `Route::resource('categories', ...)` for index/store/update/destroy; all post/category routes under `auth + verified` middleware
- ~~`resources/views/sitemap/index.blade.php`~~ ✅ (new) — XML sitemap iterating `$posts` and `$categories`
- ~~`resources/views/feed/rss.blade.php`~~ ✅ (new) — RSS 2.0 feed iterating `$posts`
- ~~Run: `php artisan wayfinder:generate`~~ ✅

**Gotchas:**
- `uploadImage` route must be defined BEFORE `Route::resource('posts', ...)` to prevent `{post}` binding from capturing it
- Flash messages must use `session()->flash('flash.success', ...)` not `session()->flash('success', ...)` — key must match what `HandleInertiaRequests` shares
- Cache key for blog index must incorporate `Cache::get('blog:index:version', 0)` to enable observer-driven invalidation
- `autosave` route needs `->middleware('throttle:60,1')`
- Run Pint after all PHP changes

**Verify:** `php artisan route:list | grep blog` shows blog routes; `php artisan route:list | grep posts` shows post routes; `php artisan test --compact` all green

---

## Phase 5: Feature Tests

**Goal:** Write and pass all PHPUnit tests for every new backend controller.

### Files
- ~~`tests/Feature/DashboardTest.php`~~ ✅ — updated: assert `stats`, `recentPosts`, `popularCategories` props; admin-vs-user scoping; 14 tests total
- ~~`tests/Feature/Posts/PostControllerTest.php`~~ ✅ (new) — cover: guest redirect, user sees own posts, admin sees all posts, create/store/edit/update/delete (happy + 403 for non-owner), autosave returns JSON, `readyToPublish` scope correctness, image upload validation
- ~~`tests/Feature/Categories/CategoryControllerTest.php`~~ ✅ (new) — cover: any user sees index, only admin creates/updates/deletes, 403 for non-admin, auto-slug, unique name validation
- ~~`tests/Feature/Blog/BlogControllerTest.php`~~ ✅ (new) — cover: index returns only published, search filter, category filter, pagination (15/page), show works for published, 404 for draft

**Patterns (follow existing test conventions):**
- Extend `Tests\TestCase`, use `RefreshDatabase`
- `$this->actingAs($user)` for auth
- `$response->assertInertia(fn (Assert $page) => $page->component(...)->has(...))`
- `UserFactory::new()->create()` (default = verified), `UserFactory::new()->admin()->create()` for admin
- `PostFactory::new()->for($user)->published()->create()` for factory chains
- Mock `ImageOptimizer` in `PostController` tests via `$this->app->bind(ImageOptimizer::class, FakeImageOptimizer::class)` in setUp; or use `UploadedFile::fake()->image()` and let real service run against temp files

**Verify:**
```bash
php artisan test --compact tests/Feature/Posts/PostControllerTest.php
php artisan test --compact tests/Feature/Categories/CategoryControllerTest.php
php artisan test --compact tests/Feature/Blog/BlogControllerTest.php
php artisan test --compact
```

---

## Phase 6: Frontend UI Primitives

**Goal:** All new Reka-UI-based components and composables are ready before feature pages need them.

### Files
- ~~`resources/js/composables/useSanitizedHtml.ts`~~ ✅ — exports `useSanitizedHtml()` returning `{ sanitize: (html: string) => string }` using DOMPurify
- ~~`resources/js/types/blog.ts`~~ ✅ (new) — `Category`, `Post`, `PaginatedPosts` type definitions; exported from `types/index.ts`; `is_admin: boolean` added to User type in `types/auth.ts`
- ~~`resources/js/components/ui/select/`~~ ✅ — `Select.vue`, `SelectTrigger.vue` (+ ChevronDown via SelectIcon), `SelectContent.vue`, `SelectItem.vue`, `SelectValue.vue`, `index.ts`
- ~~`resources/js/components/ui/table/`~~ ✅ — `Table.vue`, `TableHeader.vue`, `TableBody.vue`, `TableRow.vue`, `TableHead.vue`, `TableCell.vue`, `index.ts`
- ~~`resources/js/components/ui/tabs/`~~ ✅ — `Tabs.vue`, `TabsList.vue`, `TabsTrigger.vue`, `TabsContent.vue`, `index.ts`
- ~~`resources/js/components/ui/popover/`~~ ✅ — `Popover.vue`, `PopoverTrigger.vue`, `PopoverContent.vue`, `index.ts`
- ~~`resources/js/components/ui/sonner/`~~ ✅ — `Sonner.vue` (wraps `Toaster` from vue-sonner, reads `resolvedAppearance` from `useAppearance()`), `index.ts` (also re-exports `toast`)

**Patterns:** Follow `data-slot="component-name"` attribute, `cn()` for class binding — look at `components/ui/dialog/` as reference.

**Verify:** `npm run build` — no TypeScript errors

---

## Phase 7: Application Components, Layout Updates, and Feature Pages

**Goal:** All frontend pages and components implemented.

### Application Components
- ~~`resources/js/components/RichTextEditor.vue`~~ ✅ — TipTap editor; props: `modelValue`, `placeholder`, `maxLength`; full toolbar (bold/italic/headings/lists/blockquote/code/link/image/undo/redo); character count
- ~~`resources/js/components/CoverImageUpload.vue`~~ ✅ — drag-and-drop/click upload; props: `modelValue: File|null`, `existingImageUrl?: string`; preview, client-side type+size validation (max 5MB)
- ~~`resources/js/components/AppSidebar.vue`~~ ✅ — added Blog (Newspaper icon), Posts (FileText icon), Categories (Tag icon) to `mainNavItems` via Wayfinder `@/routes/blog`, `@/routes/posts`, `@/routes/categories`
- ~~`resources/js/layouts/app/AppSidebarLayout.vue`~~ ✅ — added `Sonner` + `toast` from `@/components/ui/sonner`; `usePage` flash watcher; `<Sonner position="bottom-right" :rich-colors="true" />`

### Pages
- ~~`resources/js/pages/ErrorPage.vue`~~ ✅ — props: `status`; maps 403/404/500/503 to titles/messages; Link back home
- ~~`resources/js/pages/Dashboard.vue`~~ ✅ — 4 stat cards, recent posts Table, popular categories Table; uses Card + Table + Badge components
- ~~`resources/js/pages/blog/Index.vue`~~ ✅ — public (no AppLayout); search, category pills, post grid with images, pagination
- ~~`resources/js/pages/blog/Show.vue`~~ ✅ — public; featured image, sanitized HTML content via `useSanitizedHtml`, related posts
- ~~`resources/js/pages/posts/Index.vue`~~ ✅ — AppLayout; Table with status Badge, Search input, status Select filter, edit/delete actions
- ~~`resources/js/pages/posts/Create.vue`~~ ✅ — `useForm` with all fields; `forceFormData: true`; RichTextEditor, CoverImageUpload, category checkboxes, SEO details
- ~~`resources/js/pages/posts/Edit.vue`~~ ✅ — same as Create but pre-populated; autosave on title/content change (debounced 2s via `router.patch`)
- ~~`resources/js/pages/posts/Show.vue`~~ ✅ — AppLayout; post preview with sanitized HTML; edit button
- ~~`resources/js/pages/categories/Index.vue`~~ ✅ — AppLayout; Table + inline Dialog create/edit; admin-gated actions via `can` flags from server

**Gotchas:**
- `blog/Index.vue` and `blog/Show.vue` are public — do NOT use `AppLayout`
- Inertia `useForm` auto-detects `File` objects and serializes as multipart — no extra configuration needed for image uploads
- Autosave in `Edit.vue` uses separate `router.patch()`, not the main form, to avoid dirty state conflicts
- Run `php artisan wayfinder:generate` before starting this phase

**Verify:** `npm run build` no errors; `php artisan test --compact` all green

---

## Phase 8: Seeder Completion and Final Validation

**Goal:** Seed data is production-quality and the full stack is verified end-to-end.

### Actions
- Verify `DatabaseSeeder` attaches categories to posts correctly; outputs admin credentials
- Run `php artisan migrate:fresh --seed` — confirm no errors
- Run `php artisan test --compact` — all tests green
- Run `npm run build` — no TypeScript errors
- Run `vendor/bin/pint --dirty --format agent` — no formatting issues
- Optionally add command test: create a past-scheduled post, run `php artisan posts:publish-scheduled`, assert status changed to Published

---

## Critical Files

| File | Phase | Why Critical |
|------|-------|--------------|
| `app/Models/Post.php` | 2 | Foundation for every other backend and frontend feature |
| `app/Models/User.php` | 2 | `isAdmin()` method drives all policy authorization |
| `app/Services/ImageOptimizer.php` | 3 | Required by PostController and PostObserver |
| `app/Http/Middleware/HandleInertiaRequests.php` | 4 | Flash props enable Sonner toast integration |
| `bootstrap/app.php` | 4 | Inertia error handling for 404/403/500/503 |
| `routes/web.php` | 4 | All new routes; triggers Wayfinder regeneration |
| `resources/js/components/AppSidebar.vue` | 7 | Navigation integration — confirms full stack wired |
| `resources/js/layouts/app/AppSidebarLayout.vue` | 7 | Sonner + flash message watcher |

## Reusable Patterns (already exist in target project)

| Pattern | Location |
|---------|----------|
| `cn()` utility for class merging | `resources/js/lib/utils.ts` |
| `useForwardPropsEmits` + `useForwardProps` for Reka UI wrappers | `components/ui/dialog/` (reference implementation) |
| `ProfileValidationRules` + `PasswordValidationRules` traits | `app/Concerns/` — extend for new form requests if needed |
| `InputError.vue` for form field errors | `components/InputError.vue` |
| `useAppearance.ts` for theme reading | `composables/useAppearance.ts` — used in Sonner |
| Wayfinder route imports | `resources/js/routes/` + `resources/js/actions/` (auto-generated) |
| `RefreshDatabase` + `actingAs` test pattern | All existing Feature tests |
