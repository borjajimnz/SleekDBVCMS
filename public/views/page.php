<?php
/** @var array $frontConfig */
/** @var array $menuStores */
/** @var array $stores */
/** @var array $navPages */
/** @var array $page */
/** @var bool $preview */

$pageTitle = $page['seo_title'] ?? $page['title'] ?? 'Page';
$metaDescription = $page['seo_description'] ?? '';
$pageSlug = $page['slug'] ?? '';
$previewBanner = $preview && empty($page['published']);

$modules = is_string($page['modules'] ?? null) ? json_decode($page['modules'], true) : ($page['modules'] ?? []);
if (!is_array($modules)) {
    $modules = [];
}

$ctx = [
    'cms' => $cms,
    'config' => $frontConfig,
    'stores' => $stores,
];

ob_start();
?>
<div class="space-y-12">
    <?php if ($previewBanner) { ?>
        <div class="px-4 py-2.5 rounded-lg bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-sm font-medium">
            Draft preview — this page is not published yet.
        </div>
    <?php } ?>

    <?php if (empty($modules)) { ?>
        <div class="text-center py-20">
            <h1 class="text-3xl sm:text-4xl font-bold"><?php print front_escape($page['title'] ?? ''); ?></h1>
            <p class="mt-3 text-gray-500 dark:text-gray-400">This page has no modules yet.</p>
        </div>
    <?php } else { ?>
        <?php foreach ($modules as $module) {
            print front_render_module(is_array($module) ? $module : [], $ctx);
        } ?>
    <?php } ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/layout.php';
