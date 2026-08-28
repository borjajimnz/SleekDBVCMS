# AGENTS.md — Manual completo del proyecto

Referencia para agentes AI que trabajan en este repositorio. Un agente debe ser capaz de **montar una web en segundos** con esta información.

---

## Qué es

**SleekDBVCMS** — CMS ligero en PHP sobre [SleekDB](https://sleekdb.github.io/) (flat-file NoSQL). Sin MySQL; cada "tabla" es una carpeta bajo `storage/stores/` con ficheros JSON.

El repositorio es a la vez:
- **La librería core** (`type: library` en Composer, publicable en Packagist como `borjajimnz/sleekdbvcms`).
- **El esqueleto de una app** (Bootstrap, entries, storage, Tailwind).

Dos front-ends en el mismo dominio:
- **Admin CMS** en `https://cms.almiapps.com/cms/` (PHP server-rendered, Tailwind CSS, dark mode).
- **Public front** en `https://cms.almiapps.com/` (Blade + Tailwind + dark mode).

---

## Quickstart — montar en segundos

### Opción A — Como dependencia (recomendado)

```bash
composer require borjajimnz/sleekdbvcms
php bin/cms install mysite   # scaffolds Config.php, storage, symlink, npm build, seed
# Visit → admin/admin / password
```

### Opción B — Clonar el repo

```bash
composer create-project borjajimnz/sleekdbvcms mysite
cd mysite
php bin/cms install [app_name]   # scaffolds Config.php, storage, symlink, npm build, seed
# Visit → admin/admin / password
```

### Actualizar motor

```bash
cd mysite
composer update borjajimnz/sleekdbvcms   # actualiza src/ (core) desde vendor/
php bin/cms publish                       # refresca el frontal (views + CSS) desde resources/front
# O ambos en uno:
php bin/cms update
```

### Comandos de `bin/cms`

| Comando | Qué hace |
|---|---|
| `install [app_name]` | Config.php desde .dist, dirs, symlink relativo, stubs, npm build, seed |
| `publish [--keep]` | Copia `resources/front/` → `public/` (reescribe por defecto; `--keep` respeta locales) |
| `update [--keep]` | `composer update` + `publish` |
| `seed` | Re-ejecuta seeding idempotente (admin, forms, modules) |

---

## Arquitectura

### Estructura del repo

```
src/                          ★ CORE + CMS admin (fijo, actualizable vía composer)
  Controllers/AdminController.php
  Services/HookManager.php
  Services/Installer.php
  Core.php
  Forms/, Interfaces/, Views/
resources/
  front/    views/** · tailwind/ · dist/tailwind.css     (REESCRIBIBLE)
  skeleton/ Bootstrap.php · public/{index,cms/index,api/index}.php
            Config.php.dist · .default_stores
            admin/custom.css.empty · admin/labels.php.empty · admin/cms_hooks.php.empty
bin/cms                       install | publish | update | seed
Bootstrap.php                 glue del sitio (user-owned, carga hooks + labels)
Config.php                    configuración (user-owned)
public/                       entry points + vistas + dist
storage/                      datos (stores, logs, cache)
```

### Autoload

`composer.json` define PSR-4 `SleekDBVCMS\` → `src/`. Las clases del core se cargan desde `vendor/borjajimnz/sleekdbvcms/src/` cuando el paquete se instala como dependencia.

### Clases de archivos

| Tipo | Qué es | En publish |
|---|---|---|
| **Frontal (core-owned)** | `public/views`, `public/dist/tailwind.css`, `tailwind/` | Se reescribe por defecto |
| **Usuario-owned** | `Config.php`, `.default_stores`, `admin/labels.php`, `cms_hooks.php`, `public/cms/custom.css` | **Nunca** se sobreescribe |

### Entradas de la app

- `public/index.php` — front controller público (Blade, router por REQUEST_URI).
- `public/cms/index.php` — admin CMS (AdminController::handleRequest).
- `public/api/index.php` — API JSON ligera.
- `Bootstrap.php` — composition root: builds `$cms`, seeds, carga hooks/labels.

### `public/storage` symlink

Debe ser **relativo**: `ln -s ../storage/public public/storage`. El instalador lo crea automáticamente.

---

## Cómo extender el CMS (sin fork)

### 1. Override ligero del admin

Archivos **user-owned** (el instalador los crea una vez; `publish` NUNCA los toca):

- **`public/cms/custom.css`** — CSS propio del admin. `src/Views/layout.php` lo incluye al final si existe.
- **`admin/labels.php`** — `[ 'Dashboard' => 'Panel', ... ]`. `Bootstrap.php` carga en `$cms->setTranslations(...)`. `Core::__($key)` consulta ese mapa.
- **`cms_hooks.php`** — registra acciones/filtros/páginas/menú. Cargado por `Bootstrap.php` si existe.

### 2. Hooks (acciones y filtros)

Hooks son callbacks que se ejecutan en puntos definidos de las vistas admin. Se registran desde `cms_hooks.php`:

```php
<?php
// $cms es la instancia de Core (disponible en Bootstrap scope)
$cms->addAction('admin_header', fn() => print '<div class="bg-blue-600 text-white px-4 py-2">Mi empresa</div>');
$cms->addFilter('admin_page_title', fn($t) => $t . ' · MiEmpresa');
```

Ver [docs/HOOKS.md](docs/HOOKS.md) para la lista completa de hooks y firmas.

### 3. Páginas admin personalizadas

```php
$cms->addAdminPage('reports', 'Reportes', function (Core $c) {
    return '<h1>Reportes</h1><p> contenido personalizado </p>';
}, 50); // position = 50 (menor = arriba en el menú)
```

La página es accesible en `?p=reports` y se registra automáticamente en el menú lateral.

### 4. Menú admin por posición

```php
$cms->addMenu('docs', 'Documentación', 'https://docs.example.com', 90);
```

Los items del menú se ordenan por `position` ascendente (menor = arriba). La barra lateral combina: stores existentes + `addAdminPage` + `addMenu` + filtro `admin_menu_items`.

---

## Archivos de usuario — los 3 que se crean

| Archivo | Propósito | Creado por |
|---|---|---|
| `admin/labels.php` | Override de etiquetas/traducciones | `bin/cms install` |
| `cms_hooks.php` | Registro de acciones/filtros/páginas/menú | `bin/cms install` |
| `public/cms/custom.css` | CSS propio del admin | `bin/cms install` |

---

## Fixes conocidos

1. **`public/storage` symlink** — debe ser relativo (`../storage/public`). El instalador lo crea.
2. **Autoload** — `composer.json` solo define `SleekDBVCMS\` (el mapping `SleekDB\` fue eliminado porque sombreaba `rakibtg/sleekdb`).
3. **Permisos** — `storage/` y `backups/` deben ser escribibles por el proceso PHP-FPM (`www-data`):
   ```bash
   sudo chown -R www-data:www-data storage backups
   sudo chmod -R 777 storage backups
   ```

---

## Permisos (causa más común de 500)

SleekDB crea carpetas con `mkdir(..., 0777)` que el umask reduce a 0755. Si la carpeta fue creada por un usuario distinto a php-fpm, los writes fallan con `IOException: Directory or file is not writable`.

El Core lo mitiga en `ensureStorageWritable()` (llamado desde `Bootstrap.php`): `umask(0)` + chmod 0777 recursivo.

---

## Verify (no hay suite de tests)

```bash
cd /tmp
curl -sk -o /dev/null -w "front home: %{http_code}\n" https://127.0.0.1/ -H "Host: cms.almiapps.com"
curl -sk -o /dev/null -w "front list: %{http_code}\n" "https://127.0.0.1/posts" -H "Host: cms.almiapps.com"
curl -sk -o /dev/null -w "cms login page: %{http_code}\n" https://127.0.0.1/cms/ -H "Host: cms.almiapps.com"
curl -sk -c c.txt -o /dev/null -w "cms login: %{http_code}\n" -X POST -d "username=admin&password=password&login=1" https://127.0.0.1/cms/ -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms dashboard: %{http_code}\n" https://127.0.0.1/cms/index.php -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms posts: %{http_code}\n" "https://127.0.0.1/cms/index.php?p=posts" -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms modules: %{http_code}\n" "https://127.0.0.1/cms/index.php?p=modules" -H "Host: cms.almiapps.com"
curl -sk -b c.txt -o /dev/null -w "cms pages: %{http_code}\n" "https://127.0.0.1/cms/index.php?p=pages" -H "Host: cms.almiapps.com"
```

Todos deben ser 200/302. `-k` porque el cert es para el dominio real.

---

## Credenciales por defecto

`admin` / `password` (seeds en `Bootstrap.php` cuando `users` store está vacío).

---

## Producción

- Live: `https://cms.almiapps.com`
- Root: `/var/www/SleekDBVCMS` (web root es `public/`)
- nginx en `cms.almiapps.com`, PHP-FPM 8.3
- Process users: php-fpm/www-data sirve requests; ubuntu posee el código.

---

## Comandos útiles

```bash
php -l file.php                                  # lint
composer install                                 # después de clone
sudo nginx -t && sudo systemctl reload nginx     # nginx config check + reload
sudo systemctl reload php8.3-fpm                 # reload PHP-FPM
tail -f /var/www/SleekDBVCMS/storage/logs/cms.log  # app error log
bin/cms publish                                  # refrescar front
```

---

## SleekDB API (v2, NO deprecated)

- Fetch all: `$store->findAll(['_id' => 'desc'])`
- Search: `$store->search([$field1, $field2], $term, ['_id' => 'desc'])`
- One by criteria: `$store->findOneBy([$field, '=', $value])`
- By id: `$store->findById($id)`
- Insert: `$store->insert($data)` → returns stored row with `_id`
- Update: `$store->update($row)` (replaces) or `$store->updateById($id, $fields)` (merges)
- Delete: `$store->deleteById($id)`

---

## Front-end templating (jenssegers/blade)

Views: `public/views/*.blade.php`, modules: `public/views/modules/*.blade.php`, compiled to `storage/blade-cache/`.

- `$cms->getBlade()->render('view.name', $data)` → `view.name` maps to `public/views/view/name.blade.php`.
- `layout.blade.php` is the master layout; pages use `@extends('layout')` + `@section('title'|'content')`.
- Modules: `front_render_module($module, $ctx)` → `modules.<type>` partials.

---

## Tailwind CSS build

```bash
npm install          # una vez tras clone
npm run build:css    # compila tailwind/input.css → public/dist/tailwind.css (minified)
```

Rebuild después de cambiar clases Tailwind en plantillas. `cms_css_url()` (en `Bootstrap.php`) añade `?v=<mtime>` para cache busting.
