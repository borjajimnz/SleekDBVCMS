# AGENTS.md

Guidance for AI coding agents working on this repository.

## What this project is

**SleekDBVCMS** — a lightweight PHP CMS on top of the [SleekDB](https://sleekdb.github.io/) flat-file NoSQL database. No MySQL/Postgres; every "table" is a folder under `storage/stores/` containing JSON files.

Two front-ends on the same domain:
- **Admin CMS** at `https://cms.almiapps.com/cms/` (server-rendered Tailwind CSS views, mobile-first, dark mode).
- **Public front** at `https://cms.almiapps.com/` (auto-discovers stores, configurable menu/header/footer in `public/config.php`, Tailwind + dark mode).

## Front-end templating (jenssegers/blade)

The **public front** is rendered with [jenssegers/blade](https://github.com/jenssegers/blade) v2.0.1 (standalone Laravel Blade). Installed via composer (pulls `illuminate/view` 11). Views live in `public/views/*.blade.php`, module partials in `public/views/modules/*.blade.php`, compiled to `storage/blade-cache/`.

**Known gotcha:** jenssegers/blade creates its own private container, but `illuminate/view` 11 resolves `blade.compiler` against the **global** `Illuminate\Container\Container`. `SleekDBVCMS\Services\BladeRenderer` fixes this by aligning the global container at construction (`Container::setInstance()`). Without this shim, rendering fails with `Class "blade.compiler" does not exist`. Don't remove it.

- `$cms->getBlade()->render('view.name', $data)` — render any view; `view.name` maps to `public/views/view/name.blade.php`.
- Views receive their data as an explicit array from `public/index.php` (no `extract()`/globals).
- `layout.blade.php` is the master layout; pages use `@extends('layout')` + `@section('title'|'meta_description'|'content')`.
- Modules: `front_render_module($module, $ctx)` (`public/index.php`) dispatches to `modules.<type>` partials (`$ctx` carries `cms`, `config`, `stores`, `blade`).
- The admin CMS (`src/Views/*.php`) is **not** Blade — it stays plain PHP templates.

## Tailwind CSS build (no CDN)

Tailwind is **compiled at build time** to `public/dist/tailwind.css` (served at `/dist/tailwind.css`). The Tailwind CDN (`cdn.tailwindcss.com`) is **not** used on any page — it penalizes PageSpeed. Rebuild after changing any Tailwind classes in templates:

```bash
npm install          # once, after clone
npm run build:css    # compiles tailwind/input.css -> public/dist/tailwind.css (minified)
```

- Config: `tailwind.config.js` (`darkMode: 'class'`). Content globs scan `public/**/*.blade.php`, `public/*.php`, `src/**/*.php`, and `storage/stores/**/*.json` + `storage/settings.json` (so classes used in stored content are picked up too).
- The `<link href="/dist/tailwind.css">` lives in `src/Views/layout.php`, `src/Views/login.php`, and `public/views/layout.blade.php`. Theme flash-prevention and dark-class toggling scripts remain inline.

## Production deployment (this server)

- Live at: `https://cms.almiapps.com`
- Root: `/var/www/SleekDBVCMS` (web root is `public/`)
- Web server: **nginx** on `cms.almiapps.com` (config `/etc/nginx/sites-available/cms.almiapps.com`), proxies PHP to **PHP-FPM 8.3** (`/run/php/php8.3-fpm.sock`)
- Process users: **php-fpm/www-data** serves requests; **ubuntu** owns the code.
- Storage must be writable by `www-data` (see Permissions below).

## Commands

```bash
php -l file.php                                  # lint a file
composer install                                 # after clone; requires network
sudo nginx -t && sudo systemctl reload nginx      # nginx config check + reload
sudo systemctl reload php8.3-fpm                  # reload PHP-FPM after config edits
tail -f /var/www/SleekDBVCMS/storage/logs/cms.log # app error log (most useful)
sudo tail -f /var/log/fpm-php.www.log             # PHP-FPM stderr/catch log
```

There is **no test suite** — verify by HTTP calls (see Verify). The admin UI's `ModulesType` ships inline vanilla JS; validate it with `node --check`.

## Architecture (DI container, consolidated)

- `Bootstrap.php` — composition root. Builds `$cms` (`SleekDBVCMS\Core`) with services, seeds admin user, wires error handlers. Does **not** dispatch.
- `public/cms/index.php` — **Admin CMS** entry. Requires `Bootstrap.php`, then runs `SleekDBVCMS\Controllers\AdminController::handleRequest()`.
- `public/index.php` — **Public front** entry. Requires `Bootstrap.php`, auto-discovers stores, renders home/list/detail. Config in `public/config.php` (menu, labels, header/footer HTML, theme). Views in `public/views/*.blade.php` (Blade).
- `public/api/index.php` — lightweight JSON API (`?users=1`). Requires `Bootstrap.php` and uses `$cms->getDatabase()`.
- `src/Core.php` — DI container: `getDatabase()`, `getAuth()`, `getConfig()`, `getFileManager()`, `getFormBuilder()`, `getLogger()`, `getBlade()`, plus helpers `log()`, `redirect()`, `now()`, `ensureStorageWritable()`.
- `src/Services/`
  - `SleekDBManager` — `DatabaseInterface` impl; wraps `SleekDB\Store`.
  - `AuthenticationService` — login/logout/session (`$_SESSION['logged']`), `setLanguage`.
  - `ConfigurationService` — loads `Config.php` + `.default_stores` JSON; enforces system stores; `getStores()`, `saveStoresFromJson()`.
  - `BladeRenderer` — jenssegers/blade wrapper (aligns the global container; see Templating section).
  - `EmailService` — minimal SMTP client for lead_form notifications (dashboard settings).
  - `FileManager` — uploads to `storage/public/FY/`, returns `/storage/FY/file`. Raster uploads (jpeg/png/gif/webp) are downscaled to `options.image_max_side` and converted to WebP at `options.image_quality` (GD; EXIF orientation applied to JPEG).
  - `Logger` — writes `storage/logs/cms.log`; registers exception/error handlers.
- `src/Controllers/AdminController.php` — all admin routes/actions; sanitizes `pages.modules` on save.
- `src/Forms/FormBuilder.php` + `src/Forms/Types/*` — input rendering per field type.
- `src/Views/{layout,login,dashboard,table,form}.php` — server-rendered templates.
- `src/Interfaces/` — `DatabaseInterface`, `AuthenticationInterface`.

`Core.legacy.php` is retired — do not reintroduce it.

## Config model

- `Config.php` — PHP array: `app_name`, `public_path`, `locale`, `upload_files_extensions_allowed`, `options` (incl. `image_max_side` default 1920, `image_quality` default 80). **Note:** it sets `$config` at global scope; `Bootstrap.php` uses `require_once` (no `return`).
- `storage/settings.json` — **runtime site settings** edited from the dashboard (`update_settings` POST → `ConfigurationService::saveSettingsFromJson()`). Keys: `site_name`, `tagline`, `blog_enabled` (bool) plus SMTP notification keys (`smtp_enabled`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_encryption`, `smtp_from_email`, `smtp_from_name`). The front (`public/index.php`) overrides `site_name`/`tagline` from these and, when `blog_enabled` is off, removes `posts`/`categories` from the menu, routes (`/posts` 404s), `store_list`/`store_item` modules, and pages whose modules reference `posts`/`categories` (home is always kept). The admin title/sidebar also uses `site_name` (fallback `app_name`).
- `.default_stores` — JSON defining content types. Format:
  ```json
  {
    "posts": {
      "title": "text",
      "body": "rich_textarea",
      "category": { "join": { "key": "category", "foreing_table": "categories", "foreing_key": "_id", "foreing_display": ["name"] } }
    }
  }
  ```
- Field types: `text`, `textarea`, `rich_textarea`, `password`, `image`, `color`, `url`, `number`, `decimal`, `email`, `datetime`, `date`, `checkbox`, `select`, `modules`, `form_fields`, and `join` blocks.
- Editable at runtime from the dashboard (writes `.default_stores`).
- `rich_textarea` renders a **Quill WYSIWYG editor** in the admin (CDN, `window.cmsRichText.init()` in `src/Views/layout.php` upgrades `.rich-editor` textareas; synced back to the hidden textarea on submit). The module builder also initializes Quill for dynamically-injected editors. On the front, rich text is rendered via `front_richtext()` (`public/index.php`) inside `.prose-html` styling (`public/views/layout.blade.php`); plain text is auto-wrapped into paragraphs.

## Store protection model

`ConfigurationService` distinguishes two concepts (both live on the same class):

- **System stores** — always re-merged into the running config by `enforceProtectedStores()` and `saveStoresFromJson()` even if removed from `.default_stores`: `users`, `pages`, `modules`, `posts`, `categories`, `redirects`, `leads`, `forms`.
- **Protected stores** (`PROTECTED_STORES = ['users']`) — system stores whose **records cannot be deleted** (`AdminController::handleStoreDelete` redirects; the delete button is hidden in `table.php`). `isProtected()` gates record deletion only.

`modules`, `posts`, `categories`, `redirects`, `leads`, `forms` are **system stores but NOT protected**: their stores must exist in config, yet records are freely deletable. `redirects` holds SEO redirect rules (source → target, HTTP code, enabled) applied by the front; `leads` holds lead_form submissions (see Pages & modules); `forms` holds the form templates referenced by `lead_form` modules.

## URL scheme

**Admin CMS** (`/cms/`):
- `GET /cms/` — dashboard (store stats + JSON config editor + backup link)
- `GET /cms/?p=<store>` — table listing; `POST` with `search=` filters rows
- `GET /cms/?p=<store>&insert=1` — create form
- `GET /cms/?p=<store>&update=1&id=N` — edit form
- `GET /cms/?p=<store>&view=1&id=N` — read-only form
- `POST` `insert_row` / `update_row` / `delete` — mutations (redirect to `?p=<store>`)
- `POST` `update_config` (body `config_file`=JSON) — save content types
- `GET /cms/?backup=1` — zip of `storage/`
- `GET /cms/?logout=1`

**Public front** (`/`) — path-based router in `public/index.php` (parses `REQUEST_URI`; nginx `try_files`/Apache `.htaccess` funnel all non-file paths to `index.php`):
- `GET /` — home = the page marked `is_home` in the `pages` store
- `GET /<page-slug>` — a page from the `pages` store (page wins over a store with the same name)
- `GET /<page-slug>?preview=1` — preview an unpublished page (needs admin session)
- `GET /<store>` — listing of a store (used by `store_list` modules; joins resolved, images shown)
- `GET /<store>/<id>` — detail page
- `GET /sitemap.xml` — XML sitemap: home, published pages (non-`is_home`), published posts (respects `blog_enabled`)
- Legacy query URLs (`?page=` / `?store=` / `?store=&id=`) still work and are 301-redirected to the pretty form (skipped when a page slug collides with the store name). Helpers: `front_store_url()`, `front_item_url()` in `public/index.php`.
- Only stores listed in `public/config.php` `menu` are reachable (users/roles excluded by default).
- **SEO redirects** (`redirects` store): before routing, `front_apply_redirects()` matches the normalized path against `enabled` rules (`source` → `target`, `code` 301/302/307/308). A matching rule issues `header('Location: target', true, code)` and exits.

## Pages & modules (template collection + per-page instances)

### `pages` store
- System + protected store. Fields: `title`, `slug` (auto-generated from title when left empty), `published` (checkbox), `show_in_menu`, `menu_order`, `is_home`, `seo_title`, `seo_description`, `modules`.
- `modules` is a custom form type (`src/Forms/Types/ModulesType.php`). Nav is generated from `pages` where `show_in_menu=1`, ordered by `menu_order`. Front routes pages by slug; drafts are 404 unless `?preview=1` with an admin session.

### `modules` store (templates)
- A normal collection (system store, records deletable) where each record is a **module template**. Fields (`ConfigurationService::DEFAULT_MODULES_DEF`):
  `title` (text), `type` (select: `hero` | `text` | `store_list` | `html` | `store_item` | `lead_form`), `subtitle` (text), `image` (image), `cta_text` (text), `cta_url` (url), `html` (rich_textarea), `store` (select of available stores), `limit` (number), `item_id` (number), plus `lead_form` config: `fields` (JSON), `notify_to` (text), `notify_cc` (text), `button_text` (text), `success_message` (text).
- In `form.php` the `modules` store's `select` fields get options: `type` → the six types; `store` → current store names.

### `forms` store (form templates) + `lead_form` module
- A `lead_form` module adds a contact form to a page and **references** a form template from the **`forms`** store via `form_id` (like `store_item` references `item_id`). The module does **not** carry the form's fields/notify config — the form entity does (module fallback fields are in `DEFAULT_MODULES_DEF`).
- `forms` store fields (`ConfigurationService::DEFAULT_FORMS_DEF`): `title`, `subtitle`, `fields` (`form_fields` builder that edits the field list as add/remove rows; stored as JSON), `notify_to`, `notify_cc`, `button_text`, `success_message`. System store, records freely editable/deletable. It's a "Sistema" store in the sidebar (`src/Views/layout.php` system list).
- Form config: `fields` is a JSON array of field definitions, e.g.
  ```json
  [{"name":"name","label":"Nombre","type":"text","required":true},{"name":"email","label":"Email","type":"email","required":true}]
  ```
  Supported field types: `text`, `email`, `tel`, `textarea`, `select` (with `options`), `checkbox`. `notify_to` / `notify_cc` are the notification recipients (CC optional). `button_text` / `success_message` customize the UI.
- Front partial: `public/views/modules/lead_form.blade.php`. It resolves the form by `form_id` from the `forms` store (module values are fallback), then renders a form that POSTs to the current page URL with hidden `lead_submit`, `lead_page`, `lead_index`.
- POST handling: `front_handle_lead_submit()` (`public/index.php`) finds the page + module instance by `lead_index`, resolves the form config from the `forms` store via `form_id` (fallback to the module), validates required fields, inserts a row into the **`leads`** store (`form`, `name`, `email`, `phone`, `company`, `message`, `page`, `payload` JSON, `created`), and — when dashboard SMTP is configured — emails `notify_to` (CC `notify_cc`, Reply-To = lead email). Success → redirect to `?sent=1`; missing required → redirect to `?error=...`.
- SMTP settings live in `storage/settings.json` (edited from the dashboard "Email notifications" section); `EmailService` (`src/Services/EmailService.php`) is a raw-socket SMTP client (STARTTLS/SSL/AUTH LOGIN). When `smtp_enabled` is off, leads are only stored (no email).

### `pages.modules` = per-page instances (NOT ids)
- When a template is added to a page, the builder **copies its values** into the page. `pages.modules` stores a JSON array of **instance objects**, each `{"_module_id":<template id>, "type":"...", ...field values}`.
- Editing an instance's values affects **only that page** — the template in the `modules` store is never modified.
- The builder (vanilla JS in `ModulesType.php`): dropdown + "Add" clones a template into the page list; a pencil button opens an inline editor showing only the fields relevant to the instance `type`; arrows reorder; ✕ removes; a hidden input `modules-hidden` holds the JSON and is what gets POSTed.
- Supported types (rendered by `front_render_module()` in `public/index.php`):
  - `hero` — `title`, `subtitle`, `image`, `cta_text`, `cta_url`
  - `text` — `html` (rich HTML)
  - `store_list` — `store`, `limit`, `title` (renders store cards, links to `/store`)
  - `html` — `html` (free HTML/iframe)
  - `store_item` — `store`, `item_id`, `title` (featured single record; reads `item_id`, not `id`)
  - `lead_form` — `title`, `form_id` (renders the referenced form from the `forms` store)
- Module rendering is Blade: `front_render_module()` delegates to `public/views/modules/<type>.blade.php`, each receiving `module` + `ctx`.
- `public/views/page.blade.php` resolves each `modules` entry: instance objects (arrays) and legacy inline module arrays are passed straight to `front_render_module()`; bare numeric ids are looked up in the `modules` store (backward compat).
- On first boot, `Bootstrap.php` seeds default module templates (one per supported type: `hero`, `text`, `store_list`, `store_item`, `html`, `lead_form`) into the `modules` store when it's empty — the `lead_form` template references the default contact form seeded in the `forms` store. Existing records are untouched (only seeds when empty).
- `AdminController::handleStoreUpdate` sanitizes `pages.modules`: keeps instance arrays as-is and converts bare numeric ids to `{"_module_id": N}`. Module images arrive as base64 data URIs (client-side FileReader) — on save they are persisted via `FileManager::uploadDataUri()` (downscaled WebP under `/storage/FY/`) so the front serves a cached file instead of a multi-MB inline blob.

## SleekDB API gotchas

The v2 `SleekDB\Store` API (NOT the deprecated `SleekDB` facade):

- Fetch all: `$store->findAll(['_id' => 'desc'])` — sorting is `['field' => 'asc'|'desc']` (not `orderBy()->fetch()`).
- Search: `$store->search([$field1, $field2], $term, ['_id' => 'desc'])`.
- One by criteria: `$store->findOneBy([$field, '=', $value])`; by id: `$store->findById($id)`.
- Insert: `$store->insert($data)` returns the stored row (adds `_id`).
- Update: `$store->update($row)` **replaces the whole document** and requires `_id`; `$store->updateById($id, $fields)` merges. Prefer `updateById` when editing partial fields.
- Delete: `$store->deleteById($id)`.
- Raw store: `new SleekDB\Store($name, $path, $options)`.
- Config deprecation: passing `options['timeout']` emits `E_USER_DEPRECATED`; the Logger filters those out.

## Permissions (most common 500 cause)

SleekDB creates folders (`cache/`, `data/`) lazily with `mkdir(..., 0777)` which the process umask can reduce to 0755. If the folder is created by a different user than php-fpm (`www-data`), writes fail with:

```
IOException: Directory or file is not writable at ".../storage/stores/<store>/cache"
```

The Core mitigates this in `ensureStorageWritable()` (called from `Bootstrap.php`): `umask(0)` + recursive `chmod 0777` on `storage/`. Fixes are: `sudo chown -R www-data:www-data storage backups && sudo chmod -R 777 storage backups`. Don't run the dev server / CLI as `ubuntu` against the same stores — it re-owns files and breaks `www-data`.

## Verify (no test suite — use HTTP)

```bash
cd /tmp
curl -sk -o /dev/null -w "front home: %{http_code}\n" https://127.0.0.1/ -H "Host: cms.almiapps.com"
curl -sk -o /dev/null -w "front list: %{http_code}\n" "https://127.0.0.1/posts" -H "Host: cms.almiapps.com"
curl -sk -o /dev/null -w "front detail: %{http_code}\n" "https://127.0.0.1/posts/2" -H "Host: cms.almiapps.com"
curl -sk -c c.txt -o /dev/null -w "cms login page: %{http_code}\n" https://127.0.0.1/cms/ -H "Host: cms.almiapps.com"
curl -sk -c c.txt -o /dev/null -w "cms login: %{http_code}\n" -X POST -d "username=admin&password=password&login=1" https://127.0.0.1/cms/ -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms dashboard: %{http_code}\n" https://127.0.0.1/cms/index.php -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms posts: %{http_code}\n" "https://127.0.0.1/cms/index.php?p=posts" -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms modules: %{http_code}\n" "https://127.0.0.1/cms/index.php?p=modules" -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms pages: %{http_code}\n" "https://127.0.0.1/cms/index.php?p=pages" -H "Host: cms.almiapps.com"
```

All should be 200/302. `-k` because the cert is for the real domain. Expect **200** for GETs and **302** for POST mutations (redirect).

## Default credentials

`admin` / `password` (seeded in `Bootstrap.php` when the `users` store is empty).

## Logs

- App: `storage/logs/cms.log` — fatal exceptions, PHP errors (deprecations filtered).
- PHP-FPM: `/var/log/fpm-php.www.log`, `/var/log/php8.3-fpm.log`.
- nginx: `/var/log/nginx/error.log` (fastcgi errors are truncated there — prefer the app/FPM logs).
