<?php
/** @var array $frontConfig */
/** @var array $menuStores */
/** @var array $stores */
/** @var string $storeName */
/** @var array $fields */
/** @var array $rows */

$pageTitle = front_label($frontConfig, $storeName);
ob_start();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl sm:text-3xl font-bold"><?php print front_escape(front_label($frontConfig, $storeName)); ?></h1>
        <span class="text-sm text-gray-500 dark:text-gray-400"><?php print count($rows); ?> items</span>
    </div>

    <?php if (empty($rows)) { ?>
        <p class="text-gray-500 dark:text-gray-400">No items in <?php print front_escape($storeName); ?> yet.</p>
    <?php } else { ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($rows as $row) {
                $img = front_image_of($frontConfig, $row, $fields);
                ?>
                <a href="<?php print front_item_url($storeName, $row['_id']); ?>"
                   class="group bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
                    <?php if ($img) { ?>
                        <div class="aspect-video bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <img src="<?php print front_escape($img); ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="">
                        </div>
                    <?php } ?>
                    <div class="p-4 space-y-2 flex-1">
                        <?php
                        $titleShown = false;
                        foreach ($fields as $fname => $ftype) {
                            if (is_array($ftype)) {
                                if (!empty($row['_join_' . $fname])) {
                                    print '<div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">' . front_escape($row['_join_' . $fname]) . '</div>';
                                }
                                continue;
                            }
                            if (in_array($ftype, $frontConfig['image_fields'], true) || in_array($ftype, $frontConfig['html_fields'], true)) {
                                continue;
                            }
                            if (empty($row[$fname])) {
                                continue;
                            }
                            if (!$titleShown && ($fname === 'title' || $fname === 'name')) {
                                print '<h3 class="font-semibold truncate">' . front_escape($row[$fname]) . '</h3>';
                                $titleShown = true;
                            } elseif ($ftype === 'textarea' || $ftype === 'text') {
                                print '<p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-3">' . front_escape(front_excerpt($row[$fname], 140)) . '</p>';
                                break;
                            } elseif ($fname === 'created' || $fname === 'date' || $fname === 'published') {
                                print '<time class="block text-xs text-gray-400 dark:text-gray-500">' . front_escape($row[$fname]) . '</time>';
                            }
                        }
                        if (!$titleShown) {
                            print '<h3 class="font-semibold truncate">#' . (int)$row['_id'] . '</h3>';
                        }
                        ?>
                    </div>
                </a>
            <?php } ?>
        </div>
    <?php } ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/layout.php';
