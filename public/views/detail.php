<?php
/** @var array $frontConfig */
/** @var array $menuStores */
/** @var array $stores */
/** @var string $storeName */
/** @var array $fields */
/** @var array $row */

$pageTitle = front_label($frontConfig, $storeName) . ' #' . (int)$row['_id'];
ob_start();

$img = front_image_of($frontConfig, $row, $fields);
?>
<div class="space-y-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400">
        <a href="/" class="hover:underline">Home</a>
        <span class="mx-1">/</span>
        <a href="<?php print front_store_url($storeName); ?>" class="hover:underline"><?php print front_escape(front_label($frontConfig, $storeName)); ?></a>
        <span class="mx-1">/</span>
        <span>#<?php print (int)$row['_id']; ?></span>
    </nav>

    <article class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <?php if ($img) { ?>
            <div class="aspect-video md:aspect-[21/9] bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <img src="<?php print front_escape($img); ?>" class="w-full h-full object-cover" alt="">
            </div>
        <?php } ?>

        <div class="p-5 sm:p-8">
            <?php
            // Title + join badges first
            $titleShown = false;
            foreach ($fields as $fname => $ftype) {
                if (!is_array($ftype)) {
                    continue;
                }
                if (!empty($row['_join_' . $fname])) {
                    print '<span class="inline-block mr-2 mb-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-xs font-medium">' . front_escape($row['_join_' . $fname]) . '</span>';
                }
            }
            ?>

            <div class="space-y-4">
                <?php foreach ($fields as $fname => $ftype) { ?>
                    <?php
                    if (is_array($ftype)) {
                        continue; // joins already shown as badges
                    }
                    if (in_array($ftype, $frontConfig['image_fields'], true)) {
                        continue; // hero image already shown
                    }
                    $value = $row[$fname] ?? '';
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    ?>
                    <?php if ($fname === 'title' || $fname === 'name') { ?>
                        <?php if (!$titleShown) { ?>
                            <h1 class="text-2xl sm:text-3xl font-bold"><?php print front_escape($value); ?></h1>
                            <?php $titleShown = true; ?>
                        <?php } ?>
                    <?php } elseif (in_array($ftype, $frontConfig['html_fields'], true)) { ?>
                        <div class="prose-html leading-relaxed"><?php print $value; ?></div>
                    <?php } elseif (in_array($ftype, ['textarea', 'text'], true)) { ?>
                        <p class="leading-relaxed whitespace-pre-line"><?php print front_escape($value); ?></p>
                    <?php } elseif ($fname === 'url') { ?>
                        <p><a href="<?php print front_escape($value); ?>" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline"><?php print front_escape($value); ?></a></p>
                    <?php } elseif (in_array($fname, ['created', 'date', 'published'], true)) { ?>
                        <time class="block text-sm text-gray-400 dark:text-gray-500"><?php print front_escape($value); ?></time>
                    <?php } else { ?>
                        <p><strong class="capitalize text-sm"><?php print front_escape($fname); ?>:</strong> <?php print front_escape($value); ?></p>
                    <?php } ?>
                <?php } ?>
                <?php if (!$titleShown) { ?>
                    <h1 class="text-2xl font-bold">Record #<?php print (int)$row['_id']; ?></h1>
                <?php } ?>
            </div>
        </div>
    </article>

    <div>
        <a href="<?php print front_store_url($storeName); ?>" class="inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to <?php print front_escape(front_label($frontConfig, $storeName)); ?>
        </a>
    </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/layout.php';
