<?php

require dirname(__DIR__) . '/Bootstrap.php';

use SleekDBVCMS\Core;

/**
 * Public front controller.
 * Routes:
 *   /                          -> home page (the page marked is_home)
 *   /?page=<slug>              -> a page from the protected "pages" store
 *   /?page=<slug>&preview=1    -> preview an unpublished page (admin session)
 *   /?store=<name>             -> listing of a store (used by store_list modules)
 *   /?store=<name>&id=N        -> detail of one record
 */

$frontConfig = require __DIR__ . '/config.php';

/** @var Core $cms */
$stores = $cms->getConfig()->getStores();

// ---- Helpers ----
function front_label(array $config, string $name): string
{
    return $config['labels'][$name] ?? ucfirst(str_replace('_', ' ', $name));
}

function front_slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function front_escape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function front_excerpt(string $text, int $len = 120): string
{
    $text = strip_tags($text);
    $text = trim($text);
    if (mb_strlen($text) <= $len) {
        return $text;
    }
    return mb_substr($text, 0, $len) . '…';
}

function front_image_of(array $config, array $row, array $fields): string
{
    foreach ($fields as $name => $type) {
        if (in_array($type, $config['image_fields'], true) && !empty($row[$name])) {
            return $row[$name];
        }
    }
    return '';
}

function front_resolve_joins(Core $cms, array $stores, string $table, array $rows): array
{
    $db = $cms->getDatabase();
    $cache = [];
    $fields = $stores[$table] ?? [];

    foreach ($rows as &$row) {
        foreach ($fields as $name => $value) {
            if (!is_array($value) || !isset($value['join'])) {
                continue;
            }
            $join = $value['join'];
            $foreignTable = $join['foreing_table'];
            $foreignKey = $join['foreing_key'] ?? '_id';
            $key = (int)($row[$join['key']] ?? 0);

            if ($key === 0) {
                $row['_join_' . $name] = '';
                continue;
            }
            if (!isset($cache[$foreignTable][$key])) {
                $cache[$foreignTable][$key] = $db->store($foreignTable)
                    ->findOneBy([$foreignKey, '=', $key]);
            }
            $foreign = $cache[$foreignTable][$key] ?? null;
            $display = '';
            if ($foreign) {
                foreach ($join['foreing_display'] as $dfield) {
                    if (isset($foreign[$dfield])) {
                        $display .= $foreign[$dfield] . ' ';
                    }
                }
            }
            $row['_join_' . $name] = trim($display);
        }
    }
    return $rows;
}

// ---- Pages ----
$db = $cms->getDatabase();
$allPages = [];
try {
    $allPages = $db->findAll('pages', ['menu_order' => 'asc']);
} catch (\Throwable $e) {
    $cms->log('front: no pages store: ' . $e->getMessage());
}

$preview = isset($_GET['preview']) && $cms->getAuth()->isLoggedIn();
$visiblePages = [];
foreach ($allPages as $page) {
    if ($preview || !empty($page['published'])) {
        $visiblePages[] = $page;
    }
}

// Normalize slug (in case it was left empty).
foreach ($visiblePages as &$page) {
    $page['slug'] = trim($page['slug'] ?? '');
    if ($page['slug'] === '') {
        $page['slug'] = front_slugify($page['title'] ?? 'page');
    }
}
unset($page);

// Nav: show_in_menu pages.
$navPages = [];
foreach ($visiblePages as $page) {
    if (!empty($page['show_in_menu'])) {
        $navPages[] = $page;
    }
}

// Store access: only stores listed in config menu.
$allowedStores = $frontConfig['menu'] === '*' ? array_keys($stores) : (array)$frontConfig['menu'];
$menuStores = [];
foreach ($allowedStores as $name) {
    if (isset($stores[$name])) {
        $menuStores[$name] = $stores[$name];
    }
}

// ---- Render module ----
function front_render_module(array $module, array $ctx): string
{
    $type = $module['type'] ?? 'text';
    $out = '';

    switch ($type) {
        case 'hero':
            $image = $module['image'] ?? '';
            $title = $module['title'] ?? '';
            $subtitle = $module['subtitle'] ?? '';
            $ctaText = $module['cta_text'] ?? '';
            $ctaUrl = $module['cta_url'] ?? '';
            $out = '<section class="relative rounded-2xl overflow-hidden bg-gray-900 text-white">';
            if ($image) {
                $out .= '<img src="' . front_escape($image) . '" class="absolute inset-0 w-full h-full object-cover opacity-40" alt="">';
            }
            $out .= '<div class="relative px-6 py-20 text-center">';
            if ($title) {
                $out .= '<h1 class="text-3xl sm:text-5xl font-bold">' . front_escape($title) . '</h1>';
            }
            if ($subtitle) {
                $out .= '<p class="mt-4 text-gray-200 max-w-xl mx-auto">' . front_escape($subtitle) . '</p>';
            }
            if ($ctaText && $ctaUrl) {
                $out .= '<a href="' . front_escape($ctaUrl) . '" class="mt-6 inline-block px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">' . front_escape($ctaText) . '</a>';
            }
            $out .= '</div></section>';
            break;

        case 'store_list':
            $store = $module['store'] ?? null;
            $limit = (int)($module['limit'] ?? 4);
            $title = $module['title'] ?? '';
            $stores = $ctx['stores'];
            $frontConfig = $ctx['config'];
            $cms = $ctx['cms'];

            if (!$store || !isset($stores[$store])) {
                break;
            }
            $rows = $cms->getDatabase()->findAll($store, ['_id' => 'desc']);
            $rows = array_slice(front_resolve_joins($cms, $stores, $store, $rows), 0, $limit);
            $fields = $stores[$store];

            $out = '<section>';
            if ($title) {
                $out .= '<div class="flex items-center justify-between mb-4">';
                $out .= '<h2 class="text-xl font-semibold">' . front_escape($title) . '</h2>';
                $out .= '<a href="/?store=' . urlencode($store) . '" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View all →</a>';
                $out .= '</div>';
            }
            if (empty($rows)) {
                $out .= '<p class="text-gray-500 dark:text-gray-400 text-sm">No items yet.</p>';
            } else {
                $out .= '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">';
                foreach ($rows as $row) {
                    $img = front_image_of($frontConfig, $row, $fields);
                    $out .= '<a href="/?store=' . urlencode($store) . '&id=' . (int)$row['_id'] . '" class="group bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow">';
                    if ($img) {
                        $out .= '<div class="aspect-video bg-gray-100 dark:bg-gray-800 overflow-hidden"><img src="' . front_escape($img) . '" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt=""></div>';
                    }
                    $out .= '<div class="p-4">';
                    $titleShown = false;
                    foreach ($fields as $fname => $ftype) {
                        if (is_array($ftype) || in_array($ftype, $frontConfig['image_fields'], true) || in_array($ftype, $frontConfig['html_fields'], true)) {
                            continue;
                        }
                        if (empty($row[$fname])) {
                            continue;
                        }
                        if (!$titleShown && ($fname === 'title' || $fname === 'name')) {
                            $out .= '<h3 class="font-semibold mb-1 truncate">' . front_escape($row[$fname]) . '</h3>';
                            $titleShown = true;
                        } elseif ($ftype === 'textarea' || $ftype === 'text') {
                            $out .= '<p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">' . front_escape(front_excerpt($row[$fname], 80)) . '</p>';
                            break;
                        }
                    }
                    if (!$titleShown) {
                        $out .= '<h3 class="font-semibold truncate">#' . (int)$row['_id'] . '</h3>';
                    }
                    $out .= '</div></a>';
                }
                $out .= '</div>';
            }
            $out .= '</section>';
            break;

        case 'store_item':
            $store = $module['store'] ?? null;
            $id = (int)($module['item_id'] ?? $module['id'] ?? 0);
            $title = $module['title'] ?? '';
            $stores = $ctx['stores'];
            $cms = $ctx['cms'];

            if (!$store || !isset($stores[$store]) || $id === 0) {
                break;
            }
            $row = $cms->getDatabase()->findById($store, $id);
            if (!$row) {
                break;
            }
            $fields = $stores[$store];
            $img = front_image_of($ctx['config'], $row, $fields);
            $out = '<section>';
            if ($title) {
                $out .= '<h2 class="text-xl font-semibold mb-4">' . front_escape($title) . '</h2>';
            }
            $out .= '<a href="/?store=' . urlencode($store) . '&id=' . $id . '" class="block bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow sm:flex">';
            if ($img) {
                $out .= '<div class="sm:w-1/3 bg-gray-100 dark:bg-gray-800"><img src="' . front_escape($img) . '" loading="lazy" class="w-full h-full object-cover" alt=""></div>';
            }
            $out .= '<div class="p-5 flex-1">';
            $out .= '<h3 class="font-semibold mb-1">' . front_escape($row['title'] ?? $row['name'] ?? '#' . $id) . '</h3>';
            foreach ($fields as $fname => $ftype) {
                if (in_array($ftype, $ctx['config']['html_fields'], true) && !empty($row[$fname])) {
                    $out .= '<p class="mt-2 prose-html">' . front_excerpt($row[$fname], 160) . '</p>';
                    break;
                }
            }
            $out .= '</div></a></section>';
            break;

        case 'html':
            $out = '<section class="prose-html">' . ($module['html'] ?? '') . '</section>';
            break;

        case 'text':
        default:
            $out = '<section class="prose-html">' . ($module['html'] ?? ($module['content'] ?? '')) . '</section>';
            break;
    }

    return $out;
}

// ---- Routing ----
$storeName = $_GET['store'] ?? null;
$pageSlug = $_GET['page'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ---- Store listing/detail (secondary, for store_list/store_item) ----
if ($storeName !== null) {
    if (!isset($stores[$storeName]) || !isset($menuStores[$storeName])) {
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        exit;
    }
    $fields = $stores[$storeName];

    if ($id !== null) {
        $row = $cms->getDatabase()->findById($storeName, $id);
        if (!$row) {
            http_response_code(404);
            require __DIR__ . '/views/404.php';
            exit;
        }
        $row = front_resolve_joins($cms, $stores, $storeName, [$row])[0];
        require __DIR__ . '/views/detail.php';
        exit;
    }

    $rows = $cms->getDatabase()->findAll($storeName, ['_id' => 'desc']);
    $rows = front_resolve_joins($cms, $stores, $storeName, $rows);
    require __DIR__ . '/views/list.php';
    exit;
}

// ---- Page ----
if ($pageSlug !== null) {
    $page = null;
    foreach ($visiblePages as $p) {
        if ($p['slug'] === $pageSlug) {
            $page = $p;
            break;
        }
    }
    if (!$page) {
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        exit;
    }
    require __DIR__ . '/views/page.php';
    exit;
}

// ---- Home: is_home page or first visible ----
$homePage = null;
foreach ($visiblePages as $p) {
    if (!empty($p['is_home'])) {
        $homePage = $p;
        break;
    }
}
if (!$homePage && !empty($visiblePages)) {
    $homePage = $visiblePages[0];
}

if ($homePage) {
    $page = $homePage;
    require __DIR__ . '/views/page.php';
    exit;
}

// ---- Fallback: no pages yet ----
$pageTitle = null;
require __DIR__ . '/views/empty.php';
