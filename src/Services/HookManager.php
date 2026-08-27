<?php

namespace SleekDBVCMS\Services;

/**
 * Lightweight hook/event system for the admin panel and front-end.
 *
 * Provides WordPress-style actions (side-effect callbacks) and filters
 * (value transformers), plus a small API to register custom admin pages
 * and sidebar menu items with a numeric position.
 *
 * Users extend the CMS without forking it by registering hooks from a
 * project-owned `cms_hooks.php` file (loaded by Bootstrap) that calls
 * methods on the Core instance, e.g.:
 *
 *   $core->addAction('admin_header', fn() => print '<div>…</div>');
 *   $core->addAdminPage('reports', 'Reports', fn($c) => '<h1>Hi</h1>', 50);
 *   $core->addMenu('docs', 'Docs', 'https://example.com', 90);
 */
class HookManager
{
    /** @var array<string, array<int, array{priority:int, cb:callable}>> */
    private array $actions = [];

    /** @var array<string, array<int, array{priority:int, cb:callable}>> */
    private array $filters = [];

    /** @var array<string, array{title:string, render:callable, position:int}> */
    private array $adminPages = [];

    /** @var array<string, array{label:string, url:string, position:int}> */
    private array $menuItems = [];

    /** @var array<string, string> */
    private array $translations = [];

    // ---- Actions -------------------------------------------------------

    public function addAction(string $hook, callable $cb, int $priority = 10): void
    {
        $this->actions[$hook][] = ['priority' => $priority, 'cb' => $cb];
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        if (empty($this->actions[$hook])) {
            return;
        }
        $items = $this->actions[$hook];
        usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);
        foreach ($items as $item) {
            ($item['cb'])(...$args);
        }
    }

    public function hasAction(string $hook): bool
    {
        return !empty($this->actions[$hook]);
    }

    // ---- Filters --------------------------------------------------------

    public function addFilter(string $hook, callable $cb, int $priority = 10): void
    {
        $this->filters[$hook][] = ['priority' => $priority, 'cb' => $cb];
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (empty($this->filters[$hook])) {
            return $value;
        }
        $items = $this->filters[$hook];
        usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);
        foreach ($items as $item) {
            $value = ($item['cb'])($value, ...$args);
        }
        return $value;
    }

    // ---- Custom admin pages --------------------------------------------

    public function addAdminPage(string $slug, string $title, callable $render, int $position = 100): void
    {
        $this->adminPages[$slug] = [
            'title' => $title,
            'render' => $render,
            'position' => $position,
        ];
        // Auto-register a sidebar entry pointing at this page.
        $this->addMenu('page:' . $slug, $title, '?p=' . urlencode($slug), $position);
    }

    public function getAdminPage(string $slug): ?array
    {
        return $this->adminPages[$slug] ?? null;
    }

    /** @return array<string, array{title:string, render:callable, position:int}> */
    public function getAdminPages(): array
    {
        return $this->adminPages;
    }

    // ---- Sidebar menu items --------------------------------------------

    public function addMenu(string $id, string $label, string $url, int $position = 100): void
    {
        $this->menuItems[$id] = [
            'label' => $label,
            'url' => $url,
            'position' => $position,
        ];
    }

    /**
     * Returns the merged list of custom sidebar entries (explicit addMenu()
     * plus auto-registered admin pages), sorted ascending by position.
     *
     * @return array<int, array{label:string, url:string, position:int}>
     */
    public function getMenuItems(): array
    {
        $items = array_values($this->menuItems);
        usort($items, fn($a, $b) => $a['position'] <=> $b['position']);
        return $items;
    }

    // ---- Translations / labels -----------------------------------------

    /** @param array<string, string> $map */
    public function setTranslations(array $map): void
    {
        foreach ($map as $key => $value) {
            $this->translations[$key] = $value;
        }
    }

    public function translate(string $key): string
    {
        return $this->translations[$key] ?? $key;
    }
}
