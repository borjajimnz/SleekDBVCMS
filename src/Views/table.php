<?php
/** @var \SleekDBVCMS\Core $core */
/** @var string $table */
/** @var array $fields */
/** @var array $rows */
/** @var string|null $search */
/** @var bool $isProtected */

$isProtected = $isProtected ?? $core->getConfig()->isProtected($table);
?>
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
    <div class="flex-1">
        <h2 class="text-lg font-semibold capitalize"><?php print htmlspecialchars($table); ?></h2>
    </div>
    <form method="post" class="flex-1 sm:max-w-xs">
        <div class="relative">
            <input name="search" type="text" value="<?php print htmlspecialchars($search ?? ''); ?>"
                   placeholder="<?php $core->_('search'); ?>"
                   class="w-full pl-8 pr-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 p-1.5 text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </div>
    </form>
    <form method="get">
        <input type="hidden" name="p" value="<?php print htmlspecialchars($table); ?>">
        <button name="insert" class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?php $core->_('New'); ?>
        </button>
    </form>
</div>

<div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-max">
            <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/50">
                    <th class="px-4 py-3 font-medium">#</th>
                    <?php foreach ($fields as $name => $value) { ?>
                        <th class="px-4 py-3 font-medium">
                            <span class="capitalize"><?php print htmlspecialchars($name); ?></span>
                            <?php if (is_array($value) && isset($value['join'])) { ?>
                                <span class="block text-xs font-normal text-gray-400 dark:text-gray-500">join: <?php print htmlspecialchars($value['join']['foreing_table']); ?></span>
                            <?php } else { ?>
                                <span class="block text-xs font-normal text-gray-400 dark:text-gray-500"><?php print htmlspecialchars(is_array($value) ? '' : $value); ?></span>
                            <?php } ?>
                        </th>
                    <?php } ?>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) { ?>
                    <tr class="border-b border-gray-100 dark:border-gray-800 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?php print $row['_id']; ?></td>
                        <?php foreach ($fields as $name => $value) { ?>
                            <?php if (is_array($value) && isset($value['join'])) { ?>
                                <td class="px-4 py-3"><?php print htmlspecialchars($row['_join_' . $name] ?? ''); ?></td>
                            <?php } else { ?>
                                <td class="px-4 py-3 max-w-[240px] truncate"><?php print htmlspecialchars($row[$name] ?? ''); ?></td>
                            <?php } ?>
                        <?php } ?>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <?php if ($table === 'pages') {
                                    $slug = trim($row['slug'] ?? '');
                                    if ($slug !== '') { ?>
                                        <a href="/?page=<?php print urlencode($slug); ?>&preview=1" target="_blank" title="Preview"
                                           class="p-1.5 rounded-lg text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-6m-1.414-6.586a2 2 0 112.828 2.828L12 14.5V17h2.5l7.086-7.086z"/></svg>
                                        </a>
                                    <?php } ?>
                                <?php } ?>
                                <form method="get">
                                    <input type="hidden" name="p" value="<?php print htmlspecialchars($table); ?>"><input type="hidden" name="id" value="<?php print $row['_id']; ?>">
                                    <button name="view" title="View"
                                            class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </form>
                                <form method="get">
                                    <input type="hidden" name="p" value="<?php print htmlspecialchars($table); ?>"><input type="hidden" name="id" value="<?php print $row['_id']; ?>">
                                    <button name="update" title="Edit"
                                            class="p-1.5 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </form>
                                <?php if (!$isProtected) { ?>
                                    <form method="post" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="p" value="<?php print htmlspecialchars($table); ?>"><input type="hidden" name="id" value="<?php print $row['_id']; ?>">
                                        <button name="delete" title="Delete"
                                                class="p-1.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (empty($rows)) { ?>
                    <tr>
                        <td colspan="<?php print count($fields) + 2; ?>" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No records</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
