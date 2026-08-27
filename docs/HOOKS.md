# HOOKS.md — Referencia completa de hooks y extensión

Todo lo que un agente necesita para extender el CMS sin forkear el core.

---

## Archivos de override (user-owned, nunca sobrescritos por `publish`)

| Archivo | Propósito |
|---|---|
| `public/cms/custom.css` | CSS propio del admin incluido al final de `layout.php` si existe |
| `admin/labels.php` | Array `[key => string]` de traducciones; `Core::__($key)` lo consulta |
| `cms_hooks.php` | Registro de acciones, filtros, páginas admin y menú; cargado por `Bootstrap.php` si existe |

---

## API de extensión (métodos de `Core`)

### Acciones (side-effects)

```php
$core->addAction(string $hook, callable $cb, int $priority = 10): void
$core->doAction(string $hook, mixed ...$args): void
$core->hasAction(string $hook): bool
```

Las acciones se ejecutan en orden de `priority` (menor = primero).

### Filtros (transforman un valor)

```php
$core->addFilter(string $hook, callable $cb, int $priority = 10): void
$core->applyFilters(string $hook, mixed $value, mixed ...$args): mixed
```

### Páginas admin personalizadas

```php
\core->addAdminPage(string $slug, string $title, callable $render, int $position = 100): void
\core->getAdminPage(string $slug): ?array
\core->getAdminPages(): array
```

`$render(Core $core)` devuelve HTML. La página se envuelve en el layout del admin (sidebar + header + main) y es accesible en `?p=<slug>`. Cada página se registra automáticamente en el menú lateral.

### Menú admin

```php
\core->addMenu(string $id, string $label, string $url, int $position = 100): void
\core->getMenuItems(): array
```

Los items se ordenan por `position` ascendente (menor = arriba). Se combinan con los stores existentes.

### Traducciones

```php
\core->setTranslations(array $map): void
\Core::__($key): string    // devuelve el override si existe, si no $key
\Core::_($key): void       // print de __()
```

---

## Slots de hooks disponibles

### Layout (src/Views/layout.php)

| Hook | Tipo | Ubicación |
|---|---|---|
| `admin_head` | action | Dentro de `<head>`, después del CSS principal |
| `admin_header` | action | Al inicio del `<header>` sticky |
| `admin_sidebar` | action | Al final del `<nav>` sidebar (después de stores y menú custom) |
| `admin_footer` | action | Antes de `</body>` |
| `admin_main_top` | action | Al inicio de `<main>`, antes del contenido |
| `admin_main_bottom` | action | Al final de `<main>`, después del contenido |
| `admin_menu_items` | filter | Filtra/añade items del menú sidebar (array de items) |

### Dashboard (src/Views/dashboard.php)

| Hook | Tipo | Ubicación |
|---|---|---|
| `admin_dashboard_top` | action | Al inicio del grid del dashboard |
| `admin_dashboard_bottom` | action | Al final del grid del dashboard |

### Tabla (src/Views/table.php)

| Hook | Tipo | Ubicación |
|---|---|---|
| `admin_table_top` | action | Antes de la tabla |
| `admin_table_bottom` | action | Después de la tabla |

### Formulario (src/Views/form.php)

| Hook | Tipo | Ubicación |
|---|---|---|
| `admin_form_top` | action | Al inicio de la tarjeta del formulario |
| `admin_form_bottom` | action | Después del `<form>` de edición |

### Login (src/Views/login.php)

| Hook | Tipo | Ubicación |
|---|---|---|
| `admin_login_top` | action | Al inicio del formulario de login |
| `admin_login_bottom` | action | Antes de `</body>` en login |

---

## Ejemplos completos

### Añadir banner en el header del admin

```php
// cms_hooks.php
$cms->addAction('admin_header', function () {
    print '<div class="bg-blue-600 text-white px-4 py-1 text-sm text-center font-medium">';
    print 'Mi empresa — Versión 2.0';
    print '</div>';
});
```

### Traducir etiquetas del admin

```php
// admin/labels.php
return [
    'Dashboard' => 'Panel principal',
    'Sign out' => 'Cerrar sesión',
    'search' => 'Buscar...',
    'New' => 'Nuevo registro',
    'Update' => 'Guardar cambios',
];
```

### Crear página admin personalizada

```php
// cms_hooks.php
$cms->addAdminPage('reports', 'Reportes', function (\SleekDBVCMS\Core $c) {
    $db = $c->getDatabase();
    $posts = $db->findAll('posts', ['_id' => 'desc']);
    $total = count($posts);
    return '<div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-200 dark:border-gray-800">'
        . '<h2 class="text-lg font-semibold mb-4">Reporte de posts</h2>'
        . '<p>Total de posts: <strong>' . $total . '</strong></p></div>';
}, 50); // position = 50 → aparece arriba en el menú
```

### Añadir item al menú admin

```php
// cms_hooks.php
$cms->addMenu('leads', 'Leads', '?p=leads', 30);  // position 30
$cms->addMenu('docs', 'Documentación', 'https://docs.example.com', 90);
```

### Filtrar items del menú

```php
// cms_hooks.php — ordenar o eliminar items existentes
$cms->addFilter('admin_menu_items', function (array $items) {
    return array_filter($items, fn($item) => $item['label'] !== 'users');
});
```

### Inyectar scripts en el head del admin

```php
// cms_hooks.php
$cms->addAction('admin_head', function () {
    print '<script src="https://cdn.example.com/analytics.js"></script>';
});
```

### Inyectar contenido en la página principal (front-end)

```php
// cms_hooks.php — hooks front-end también disponibles
$cms->addAction('front_footer', function () {
    print '<div class="text-center py-4 text-sm text-gray-400">© 2026 Mi empresa</div>';
});
```

---

## Flujo de carga

1. `Bootstrap.php` carga `vendor/autoload.php`.
2. Crea `$cms` (Core) con todos los servicios.
3. Llama `$cms->install()` (seeding idempotente).
4. Carga `cms_hooks.php` si existe → registra acciones/filtros/páginas/menú.
5. Carga `admin/labels.php` si existe → `$cms->setTranslations(...)`.
6. `AdminController::handleRequest()` lee las páginas registradas vía `getAdminPage()`.
7. Las vistas admin llaman `$core->doAction(...)` / `$core->applyFilters(...)` / `$core->__()`.

---

## Comandos de console

```bash
php bin/cms install [app_name]   # scaffolds todo desde resources/
php bin/cms publish [--keep]     # refresca el frontal desde resources/front/
php bin/cms update [--keep]      # composer update + publish
php bin/cms seed                 # re-ejecuta seeding
```
