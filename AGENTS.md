# AGENTS.md

Guidance for AI coding agents working on this repository.

## What this project is

**SleekDBVCMS** — a lightweight PHP CMS on top of the [SleekDB](https://sleekdb.github.io/) flat-file NoSQL database. No MySQL/Postgres; every "table" is a folder under `storage/stores/` containing JSON files.

Two front-ends on the same domain:
- **Admin CMS** at `https://cms.almiapps.com/cms/` (server-rendered Tailwind CSS views, mobile-first, dark mode).
- **Public front** at `https://cms.almiapps.com/` (auto-discovers stores, configurable menu/header/footer in `public/config.php`, Tailwind + dark mode).

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

There is **no test suite** and no `npm`/`yarn` — verify by HTTP calls (see Verify). The admin UI's `ModulesType` ships inline vanilla JS; validate it with `node --check`.

## Architecture (DI container, consolidated)

- `Bootstrap.php` — composition root. Builds `$cms` (`SleekDBVCMS\Core`) with services, seeds admin user, wires error handlers. Does **not** dispatch.
- `public/cms/index.php` — **Admin CMS** entry. Requires `Bootstrap.php`, then runs `SleekDBVCMS\Controllers\AdminController::handleRequest()`.
- `public/index.php` — **Public front** entry. Requires `Bootstrap.php`, auto-discovers stores, renders home/list/detail. Config in `public/config.php` (menu, labels, header/footer HTML, theme). Views in `public/views/`.
- `public/api/index.php` — lightweight JSON API (`?users=1`). Requires `Bootstrap.php` and uses `$cms->getDatabase()`.
- `src/Core.php` — DI container: `getDatabase()`, `getAuth()`, `getConfig()`, `getFileManager()`, `getFormBuilder()`, `getLogger()`, plus helpers `log()`, `redirect()`, `now()`, `ensureStorageWritable()`.
- `src/Services/`
  - `SleekDBManager` — `DatabaseInterface` impl; wraps `SleekDB\Store`.
  - `AuthenticationService` — login/logout/session (`$_SESSION['logged']`), `setLanguage`.
  - `ConfigurationService` — loads `Config.php` + `.default_stores` JSON; enforces system stores; `getStores()`, `saveStoresFromJson()`.
  - `FileManager` — uploads to `storage/public/FY/`, returns `/storage/FY/file`. Raster uploads (jpeg/png/gif/webp) are downscaled to `options.image_max_side` and converted to WebP at `options.image_quality` (GD; EXIF orientation applied to JPEG).
  - `Logger` — writes `storage/logs/cms.log`; registers exception/error handlers.
- `src/Controllers/AdminController.php` — all admin routes/actions; sanitizes `pages.modules` on save.
- `src/Forms/FormBuilder.php` + `src/Forms/Types/*` — input rendering per field type.
- `src/Views/{layout,login,dashboard,table,form}.php` — server-rendered templates.
- `src/Interfaces/` — `DatabaseInterface`, `AuthenticationInterface`.

`Core.legacy.php` is the **retired monolith** (formerly `Core.php`). Do not reintroduce it; nothing references it.

## Config model

- `Config.php` — PHP array: `app_name`, `public_path`, `locale`, `upload_files_extensions_allowed`, `options` (incl. `image_max_side` default 1920, `image_quality` default 80). **Note:** it sets `$config` at global scope; `Bootstrap.php` uses `require_once` (no `return`).
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
- Field types: `text`, `textarea`, `rich_textarea`, `password`, `image`, `color`, `url`, `number`, `decimal`, `email`, `datetime`, `date`, `checkbox`, `select`, `modules`, and `join` blocks.
- Editable at runtime from the dashboard (writes `.default_stores`).

## Store protection model

`ConfigurationService` distinguishes two concepts (both live on the same class):

- **System stores** — always re-merged into the running config by `enforceProtectedStores()` and `saveStoresFromJson()` even if removed from `.default_stores`: `users`, `pages`, `modules`.
- **Protected stores** (`PROTECTED_STORES = ['users', 'pages']`) — system stores whose **records cannot be deleted** (`AdminController::handleStoreDelete` redirects; the delete button is hidden in `table.php`). `isProtected()` gates record deletion only.

`modules` is a **system store but NOT protected**: its store must exist in config, yet module records are freely deletable.

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
- Legacy query URLs (`?page=` / `?store=` / `?store=&id=`) still work and are 301-redirected to the pretty form (skipped when a page slug collides with the store name). Helpers: `front_store_url()`, `front_item_url()` in `public/index.php`.
- Only stores listed in `public/config.php` `menu` are reachable (users/roles excluded by default).

## Pages & modules (template collection + per-page instances)

### `pages` store
- System + protected store. Fields: `title`, `slug` (auto-generated from title when left empty), `published` (checkbox), `show_in_menu`, `menu_order`, `is_home`, `seo_title`, `seo_description`, `modules`.
- `modules` is a custom form type (`src/Forms/Types/ModulesType.php`). Nav is generated from `pages` where `show_in_menu=1`, ordered by `menu_order`. Front routes pages by slug; drafts are 404 unless `?preview=1` with an admin session.

### `modules` store (templates)
- A normal collection (system store, records deletable) where each record is a **module template**. Fields (`ConfigurationService::DEFAULT_MODULES_DEF`):
  `title` (text), `type` (select: `hero` | `text` | `store_list` | `html` | `store_item`), `subtitle` (text), `image` (image), `cta_text` (text), `cta_url` (url), `html` (rich_textarea), `store` (select of available stores), `limit` (number), `item_id` (number).
- In `form.php` the `modules` store's `select` fields get options: `type` → the five types; `store` → current store names.

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
- `public/views/page.php` resolves each `modules` entry: instance objects (arrays) and legacy inline module arrays are passed straight to `front_render_module()`; bare numeric ids are looked up in the `modules` store (backward compat).
- `AdminController::handleStoreUpdate` sanitizes `pages.modules`: keeps instance arrays as-is and converts bare numeric ids to `{"_module_id": N}`.

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
