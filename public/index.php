<?php

require dirname(__DIR__) . '/Bootstrap.php';

use SleekDBVCMS\Core;

/**
 * Public front controller.
 * Routes (pretty URLs via REQUEST_URI; nginx/Apache funnel all paths to this file):
 *   /                          -> home page (the page marked is_home)
 *   /<page-slug>               -> a page from the protected "pages" store
 *   /<page-slug>?preview=1     -> preview an unpublished page (admin session)
 *   /<store>                   -> listing of a store (used by store_list modules)
 *   /<store>/<id>              -> detail of one record
 * Legacy query URLs (?page= / ?store=) are accepted and 301-redirected to the
 * pretty form so old links keep working with a single canonical URL.
 */

$frontConfig = require __DIR__ . '/config.php';

/** @var Core $cms */
$stores = $cms->getConfig()->getStores();

// Merge runtime site settings from the CMS dashboard (name, tagline, blog on/off).
$settings = $cms->getConfig()->getSettings();
$frontConfig['site_name'] = (string)($settings['site_name'] ?? $frontConfig['site_name']);
$frontConfig['tagline'] = (string)($settings['tagline'] ?? $frontConfig['tagline'] ?? '');
$frontConfig['blog_enabled'] = !empty($settings['blog_enabled']);

// The blog is disabled: hide posts/categories from the menu and routing.
if (empty($frontConfig['blog_enabled'])) {
    if ($frontConfig['menu'] !== '*') {
        $frontConfig['menu'] = array_values(array_diff($frontConfig['menu'], ['posts', 'categories']));
    } else {
        $frontConfig['menu'] = array_values(array_diff(array_keys($stores), ['posts', 'categories']));
    }
}

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

/**
 * Inject width/height (and decoding="async") into <img> tags pointing at local
 * /storage/ files. Without dimensions the browser can't reserve space, so lazy
 * images snap open on load and push the layout down (CLS). Dimensions are read
 * once per file and cached for the request.
 */
function front_enrich_images(string $html): string
{
    static $dims = [];
    return preg_replace_callback('/<img\s+([^>]*)>/i', function ($m) use (&$dims) {
        $attrs = $m[1];
        if (!preg_match('/\bsrc="(\/storage\/[^"]+)"/', $attrs, $sm)
            || preg_match('/\b(?:width|height)=/i', $attrs)) {
            return $m[0];
        }
        $src = $sm[1];
        if (!array_key_exists($src, $dims)) {
            // URL path /storage/... maps to public/storage/... (symlink).
            $info = @getimagesize(dirname(__DIR__) . '/public' . $src);
            $dims[$src] = is_array($info) ? [$info[0], $info[1]] : null;
        }
        if (!$dims[$src]) {
            return $m[0];
        }
        $extra = sprintf(' width="%d" height="%d"', $dims[$src][0], $dims[$src][1]);
        if (!preg_match('/\bdecoding=/i', $attrs)) {
            $extra .= ' decoding="async"';
        }
        return '<img ' . $attrs . $extra . '>';
    }, $html);
}

function front_richtext(?string $html): string
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }
    // Plain text (no tags): wrap lines into paragraphs so it renders like HTML.
    if (preg_match('/<[a-z][^>]*>/i', $html) !== 1) {
        $paragraphs = preg_split('/\r?\n\r?\n/', $html) ?: [];
        $out = [];
        foreach ($paragraphs as $p) {
            $out[] = '<p>' . nl2br(front_escape($p)) . '</p>';
        }
        return implode("\n", $out);
    }
    return front_enrich_images($html);
}

function front_store_url(string $name): string
{
    return '/' . rawurlencode($name);
}

function front_item_url(string $name, $id): string
{
    return front_store_url($name) . '/' . (int)$id;
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

// Blog disabled: drop pages whose modules reference posts/categories so they
// disappear from the nav and their routes 404 (like the blog stores do).
// The home page is always kept visible.
if (empty($frontConfig['blog_enabled'])) {
    $visiblePages = array_values(array_filter($visiblePages, function ($page) {
        if (!empty($page['is_home'])) {
            return true;
        }
        $modules = is_string($page['modules'] ?? null) ? json_decode($page['modules'], true) : ($page['modules'] ?? []);
        if (!is_array($modules)) {
            return true;
        }
        foreach ($modules as $entry) {
            if (is_array($entry) && isset($entry['store']) && in_array($entry['store'], ['posts', 'categories'], true)) {
                return false;
            }
        }
        return true;
    }));
}

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

/**
 * Build the nested menu tree for one location (header|footer) from the
 * "menus" store: only enabled items, ordered by the order field, grouped by
 * their parent (self-join). Items reference internal/external targets via the
 * url field (plain URL string, e.g. "/about", "/products" or "https://...").
 */
function front_menu_tree(Core $cms, array $stores, string $location): array
{
    if (!isset($stores['menus'])) {
        return [];
    }
    try {
        $items = $cms->getDatabase()->findAll('menus', ['order' => 'asc']);
    } catch (\Throwable $e) {
        return [];
    }

    $byId = [];
    foreach ($items as $item) {
        if (empty($item['enabled'])) {
            continue;
        }
        if ((string)($item['location'] ?? '') !== $location) {
            continue;
        }
        $id = (int)($item['_id'] ?? 0);
        if ($id > 0) {
            $item['children'] = [];
            $byId[$id] = $item;
        }
    }

    $tree = [];
    foreach ($byId as $id => &$node) {
        $parent = (int)($node['parent'] ?? 0);
        if ($parent > 0 && isset($byId[$parent])) {
            $byId[$parent]['children'][] = &$node;
        } else {
            $tree[] = &$node;
        }
    }
    unset($node);
    return $tree;
}

// When the menus store has header/footer items they replace the legacy
// navPages navigation; otherwise the old behaviour is kept as fallback.
$frontConfig['menus'] = [
    'header' => front_menu_tree($cms, $stores, 'header'),
    'footer' => front_menu_tree($cms, $stores, 'footer'),
];

// ---- Render module ----
function front_render_module(array $module, array $ctx): string
{
    $type = $module['type'] ?? 'text';
    $allowed = ['hero', 'text', 'html', 'store_list', 'store_item', 'lead_form', 'cta', 'split', 'features', 'stats', 'testimonials', 'faq', 'pricing', 'logos', 'video'];
    if (!in_array($type, $allowed, true)) {
        $type = 'text';
    }
    $blade = $ctx['blade'] ?? null;
    if (!$blade instanceof \SleekDBVCMS\Services\BladeRenderer) {
        $blade = $ctx['cms']->getBlade();
    }
    return $blade->render('modules.' . $type, ['module' => $module, 'ctx' => $ctx]);
}

// ---- Lead form submission (POST) ----
function front_handle_lead_submit(Core $cms, array $stores, array $visiblePages): void
{
    $slug = trim((string)($_POST['lead_page'] ?? ''));
    $index = (int)($_POST['lead_index'] ?? 0);

    // Locate the page that owns the form.
    $page = null;
    foreach ($visiblePages as $p) {
        if (trim($p['slug'] ?? '') === $slug) {
            $page = $p;
            break;
        }
    }
    if (!$page) {
        return;
    }

    // Find the lead_form module instance at the posted index.
    $moduleRefs = is_string($page['modules'] ?? null) ? json_decode($page['modules'], true) : ($page['modules'] ?? []);
    if (!is_array($moduleRefs)) {
        return;
    }
    $resolved = [];
    foreach ($moduleRefs as $entry) {
        if (is_array($entry)) {
            $resolved[] = $entry;
        } else {
            $moduleId = (int)$entry;
            if ($moduleId > 0) {
                $module = $cms->getDatabase()->findById('modules', $moduleId);
                if ($module) {
                    $resolved[] = $module;
                }
            }
        }
    }
    if (!isset($resolved[$index]) || (($resolved[$index]['type'] ?? '') !== 'lead_form')) {
        return;
    }
    $moduleConfig = $resolved[$index];

    // lead_form modules reference a form template from the "forms" store.
    // The form's fields/notify settings win; the module instance is a fallback.
    $formConfig = $moduleConfig;
    $formId = (int)($moduleConfig['form_id'] ?? 0);
    if ($formId > 0) {
        $form = $cms->getDatabase()->findById('forms', $formId);
        if (is_array($form)) {
            foreach (['title', 'subtitle', 'fields', 'notify_to', 'notify_cc', 'button_text', 'success_message'] as $key) {
                if (isset($form[$key]) && trim((string)$form[$key]) !== '') {
                    $formConfig[$key] = $form[$key];
                }
            }
        }
    }

    // Validate required fields against the configured field definitions.
    $fieldDefs = is_string($formConfig['fields'] ?? null) ? json_decode($formConfig['fields'], true) : ($formConfig['fields'] ?? []);
    if (!is_array($fieldDefs)) {
        $fieldDefs = [];
    }
    $payload = [];
    foreach ($fieldDefs as $field) {
        if (!is_array($field) || empty($field['name'])) {
            continue;
        }
        $name = $field['name'];
        $value = trim((string)($_POST[$name] ?? ''));
        if (!empty($field['required']) && $value === '') {
            header('Location: /' . rawurlencode($slug) . '?error=' . urlencode('Please fill in all required fields.'));
            exit;
        }
        $payload[$name] = $value;
    }

    // Persist the lead.
    $cms->getDatabase()->insert('leads', [
        'form' => (string)($formConfig['title'] ?? 'Lead form'),
        'name' => (string)($payload['name'] ?? ''),
        'email' => (string)($payload['email'] ?? ''),
        'phone' => (string)($payload['phone'] ?? ''),
        'company' => (string)($payload['company'] ?? ''),
        'message' => (string)($payload['message'] ?? ''),
        'page' => $slug,
        'payload' => json_encode($payload),
        'created' => date('Y-m-d H:i:s'),
    ]);

    // Notify by email when SMTP is configured on the dashboard.
    $to = trim((string)($formConfig['notify_to'] ?? ''));
    $cc = trim((string)($formConfig['notify_cc'] ?? ''));
    if ($to !== '' && $cms->getEmail()->isConfigured()) {
        $subject = 'New lead: ' . ((string)($formConfig['title'] ?? 'Lead form')) . ' (' . $slug . ')';
        $body = front_lead_email_html($payload, $formConfig, $slug);
        $replyTo = (string)($payload['email'] ?? '');
        $cms->getEmail()->send($to, $subject, $body, $cc, $replyTo);
    }

    header('Location: /' . rawurlencode($slug) . '?sent=1');
    exit;
}

function front_lead_email_html(array $payload, array $formConfig, string $slug): string
{
    $rows = '';
    foreach ($payload as $name => $value) {
        if ($value === '') {
            continue;
        }
        $rows .= '<tr><td style="padding:6px 12px;font-weight:600;vertical-align:top;white-space:nowrap;">'
            . htmlspecialchars(ucfirst(str_replace('_', ' ', $name)), ENT_QUOTES, 'UTF-8')
            . '</td><td style="padding:6px 12px;vertical-align:top;">'
            . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'))
            . '</td></tr>';
    }
    $title = htmlspecialchars((string)($formConfig['title'] ?? 'Lead form'), ENT_QUOTES, 'UTF-8');
    return '<html><body style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111;">'
        . '<h2 style="margin:0 0 16px;">' . $title . '</h2>'
        . '<p style="margin:0 0 8px;color:#666;">Submitted from: /' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<table style="border-collapse:collapse;border:1px solid #e5e7eb;width:100%;">' . $rows . '</table>'
        . '</body></html>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead_submit'])) {
    front_handle_lead_submit($cms, $stores, $visiblePages);
}

// ---- Browser cache for public pages ----
// Published pages are static-ish for visitors; let the browser cache plain
// GET responses briefly so repeat loads and crawlers skip the PHP render.
// Preview requests and logged-in admins always get fresh content. Overrides
// the no-store header PHP's session cache limiter would otherwise emit.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$preview && !$cms->getAuth()->isLoggedIn()) {
    header('Cache-Control: public, max-age=300');
}

// ---- Sitemap.xml ----
function front_site_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host;
}

function front_xml_escape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function front_render_sitemap(Core $cms, array $stores, array $visiblePages, array $frontConfig): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $base = front_site_url();
    $urls = [];

    $urls[] = ['loc' => $base . '/'];

    foreach ($visiblePages as $p) {
        $slug = trim($p['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        if (!empty($p['is_home'])) {
            continue;
        }
        $urls[] = ['loc' => $base . '/' . rawurlencode($slug)];
    }

    if (!empty($frontConfig['blog_enabled']) && isset($stores['posts'])) {
        try {
            $posts = $cms->getDatabase()->findAll('posts', ['_id' => 'desc']);
        } catch (\Throwable $e) {
            $posts = [];
        }
        foreach ($posts as $post) {
            if (empty($post['published'])) {
                continue;
            }
            $entry = ['loc' => $base . front_item_url('posts', $post['_id'])];
            if (!empty($post['published_at'])) {
                $ts = strtotime((string)$post['published_at']);
                if ($ts) {
                    $entry['lastmod'] = date('Y-m-d', $ts);
                }
            }
            $urls[] = $entry;
        }
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        echo "\t<url>\n";
        echo "\t\t<loc>" . front_xml_escape($u['loc']) . "</loc>\n";
        if (!empty($u['lastmod'])) {
            echo "\t\t<lastmod>" . front_xml_escape($u['lastmod']) . "</lastmod>\n";
        }
        echo "\t</url>\n";
    }
    echo '</urlset>' . "\n";
    exit;
}

// ---- SEO redirects ----
function front_apply_redirects(Core $cms, array $stores, string $path): void
{
    if (!isset($stores['redirects'])) {
        return;
    }
    try {
        $redirects = $cms->getDatabase()->findAll('redirects');
    } catch (\Throwable $e) {
        return;
    }
    $current = '/' . ltrim(rtrim($path, '/'), '/');

    foreach ($redirects as $rule) {
        if (empty($rule['enabled'])) {
            continue;
        }
        $source = trim((string)($rule['source'] ?? ''));
        $target = trim((string)($rule['target'] ?? ''));
        if ($source === '' || $target === '') {
            continue;
        }
        $source = '/' . ltrim(rtrim($source, '/'), '/');
        if ($source === $current) {
            $code = (int)($rule['code'] ?? 301);
            if (!in_array($code, [301, 302, 307, 308], true)) {
                $code = 301;
            }
            header('Location: ' . $target, true, $code);
            exit;
        }
    }
}

// ---- Routing ----
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === false || $path === null) {
    $path = '/';
}
$path = rtrim($path, '/');
$segments = [];
foreach (explode('/', $path) as $seg) {
    if ($seg !== '') {
        $segments[] = urldecode($seg);
    }
}
if ($segments === ['index.php']) {
    $segments = [];
}

// Sitemap.xml endpoint (before page/store routing).
if ($path === '/sitemap.xml' || $path === '/sitemap') {
    front_render_sitemap($cms, $stores, $visiblePages, $frontConfig);
}

// SEO redirects from the "redirects" store.
front_apply_redirects($cms, $stores, $path);

$storeName = null;
$pageSlug = null;
$id = null;

// Legacy query-string routes -> 301 redirect to the pretty URL.
if ($segments === []) {
    if (isset($_GET['page'])) {
        $target = '/' . rawurlencode((string)$_GET['page']);
        if (isset($_GET['preview'])) {
            $target .= '?preview=1';
        }
        header('Location: ' . $target, true, 301);
        exit;
    }
    if (isset($_GET['store'])) {
        $name = (string)$_GET['store'];
        if (isset($stores[$name]) && isset($menuStores[$name])) {
            $target = front_store_url($name);
            if (isset($_GET['id'])) {
                $target .= '/' . (int)$_GET['id'];
            }
            // A page whose slug equals the store name wins on the pretty route,
            // so only redirect when there is no such page (legacy URL keeps working).
            $pageConflict = false;
            foreach ($visiblePages as $p) {
                if ($p['slug'] === $name) {
                    $pageConflict = true;
                    break;
                }
            }
            if (!$pageConflict) {
                header('Location: ' . $target, true, 301);
                exit;
            }
        }
    }
    $storeName = $_GET['store'] ?? null;
    $pageSlug = $_GET['page'] ?? null;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
} elseif (count($segments) === 1) {
    // Single segment: page slug wins, otherwise a store listing.
    $pageSlug = $segments[0];
    $isPage = false;
    foreach ($visiblePages as $p) {
        if ($p['slug'] === $pageSlug) {
            $isPage = true;
            break;
        }
    }
    if (!$isPage) {
        $storeName = $pageSlug;
        $pageSlug = null;
    }
} else {
    // Two+ segments: store detail.
    $storeName = $segments[0];
    $id = ctype_digit($segments[1]) ? (int)$segments[1] : null;
    if ($id === null) {
        http_response_code(404);
        echo $cms->getBlade()->render('404', ['frontConfig' => $frontConfig, 'navPages' => $navPages, 'storeName' => $storeName, 'pageSlug' => $pageSlug]);
        exit;
    }
}

// ---- Store listing/detail (secondary, for store_list/store_item) ----
if ($storeName !== null) {
    if (!isset($stores[$storeName]) || !isset($menuStores[$storeName])) {
        http_response_code(404);
        echo $cms->getBlade()->render('404', ['frontConfig' => $frontConfig, 'navPages' => $navPages, 'storeName' => $storeName, 'pageSlug' => $pageSlug]);
        exit;
    }
    $fields = $stores[$storeName];

    if ($id !== null) {
        $row = $cms->getDatabase()->findById($storeName, $id);
        if (!$row) {
            http_response_code(404);
            echo $cms->getBlade()->render('404', ['frontConfig' => $frontConfig, 'navPages' => $navPages, 'storeName' => $storeName, 'pageSlug' => $pageSlug]);
            exit;
        }
        $row = front_resolve_joins($cms, $stores, $storeName, [$row])[0];
        echo $cms->getBlade()->render('detail', [
            'frontConfig' => $frontConfig,
            'menuStores' => $menuStores,
            'stores' => $stores,
            'navPages' => $navPages,
            'storeName' => $storeName,
            'pageSlug' => $pageSlug,
            'fields' => $fields,
            'row' => $row,
        ]);
        exit;
    }

    $rows = $cms->getDatabase()->findAll($storeName, ['_id' => 'desc']);
    $rows = front_resolve_joins($cms, $stores, $storeName, $rows);
    echo $cms->getBlade()->render('list', [
        'frontConfig' => $frontConfig,
        'menuStores' => $menuStores,
        'stores' => $stores,
        'navPages' => $navPages,
        'storeName' => $storeName,
        'pageSlug' => $pageSlug,
        'fields' => $fields,
        'rows' => $rows,
    ]);
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
        echo $cms->getBlade()->render('404', ['frontConfig' => $frontConfig, 'navPages' => $navPages, 'storeName' => $storeName, 'pageSlug' => $pageSlug]);
        exit;
    }
    echo $cms->getBlade()->render('page', [
        'frontConfig' => $frontConfig,
        'menuStores' => $menuStores,
        'stores' => $stores,
        'navPages' => $navPages,
        'storeName' => $storeName,
        'pageSlug' => $pageSlug,
        'page' => $page,
        'preview' => $preview,
        'cms' => $cms,
        'blade' => $cms->getBlade(),
    ]);
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
    echo $cms->getBlade()->render('page', [
        'frontConfig' => $frontConfig,
        'menuStores' => $menuStores,
        'stores' => $stores,
        'navPages' => $navPages,
        'storeName' => $storeName,
        'pageSlug' => $pageSlug,
        'page' => $page,
        'preview' => $preview,
        'cms' => $cms,
        'blade' => $cms->getBlade(),
    ]);
    exit;
}

// ---- Fallback: no pages yet ----
echo $cms->getBlade()->render('empty', [
    'frontConfig' => $frontConfig,
    'navPages' => $navPages,
    'storeName' => $storeName,
    'pageSlug' => $pageSlug,
]);
