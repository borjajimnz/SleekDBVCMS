# Changelog

All notable changes to SleekDBVCMS are documented in this file.

## [3.0] - 2026-08-13

### Added
- Blade-based public front-end (`public/views/*.blade.php`) via jenssegers/blade with a `BladeRenderer` service.
- Pretty URL router for the public front (`/<page>`, `/<store>`, `/<store>/<id>`), legacy query URLs 301-redirected, `?preview=1` for draft pages.
- `pages` + `modules` system: pages are built from reusable module templates. Templates hold only configuration (schema); each page stores its own empty instances. 15 module types (hero, text, store list, store item, lead form, CTA, split, features, stats, testimonials, FAQ, pricing, logos, video, HTML).
- `forms` + `leads` system: `lead_form` modules reference form templates; submissions stored in `leads` and optionally emailed via SMTP (`EmailService`).
- `menus` system store: header/footer navigation with sub-menus, internal-link picker and external URLs.
- `redirects` store: SEO redirect rules (301/302/307/308).
- XML sitemap at `/sitemap.xml`.
- JSON API entry at `public/api/`.
- `rich_textarea` now uses Quill WYSIWYG editor.
- CMS sidebar reorganized into "Contenido" / "Sistema" sections with a "View site" link.
- Dark mode support and flash-prevention across admin and front.

### Changed
- Admin UI migrated from Bootstrap to Tailwind CSS compiled at build time (`npm run build:css`, no CDN).
- Raster uploads downscaled to `options.image_max_side` and converted to WebP (`options.image_quality`, GD with EXIF orientation).
- Base64 module/repeater images persisted as optimized WebP files under `storage/public/FY/`.
- `pages` records are now deletable (only `users` remains protected).
- `modules` column hidden in the pages table listing.

### Fixed
- Front menu HTML structure.
- Menus internal-link picker now syncs the selected value into the `url` field and saves it.

## [2.0] - 2026-08-13

- Major upgrade (feature/upgrading branch).

## [1.3]

- Minor improvements.

## [1.2.1]

- Bug fixes.

## [1.2]

- Feature release.

## [1.1]

- Feature release.

## [1.0]

- Initial release.
