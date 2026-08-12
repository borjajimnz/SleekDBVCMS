<?php
/** @var \SleekDBVCMS\Core $core */
/** @var array $stats */
/** @var string $json */
/** @var string|null $msg */
/** @var string|null $backupMsg */
/** @var array $users */
?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Left column -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-semibold">Welcome to your new *basic* web app</h2>
            </div>
            <div class="p-5 space-y-4 text-sm">
                <p class="text-gray-600 dark:text-gray-400">Assignable types of inputs for the store configuration:</p>
                <p class="flex flex-wrap gap-1.5">
                    <?php foreach (['select', 'text', 'image', 'password', 'color', 'url', 'number', 'email', 'decimal', 'textarea', 'rich_textarea', 'date', 'datetime'] as $t) { ?>
                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-mono"><?php print $t; ?></span>
                    <?php } ?>
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Stores</h2>
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300"><?php print count($stats); ?></span>
            </div>
            <div class="p-5">
                <div class="overflow-x-auto -mx-5 px-5">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                                <th class="py-2 pr-4">#</th>
                                <th class="py-2 pr-4">Store</th>
                                <th class="py-2 pr-4">Fields</th>
                                <th class="py-2 text-right">Records</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stats as $i => $stat) { ?>
                            <tr class="border-b border-gray-100 dark:border-gray-800 last:border-0">
                                <td class="py-2.5 pr-4 text-gray-500 dark:text-gray-400"><?php print $i + 1; ?></td>
                                <td class="py-2.5 pr-4"><a class="text-blue-600 dark:text-blue-400 hover:underline" href="?p=<?php print urlencode($stat['name']); ?>"><?php print htmlspecialchars($stat['name']); ?></a></td>
                                <td class="py-2.5 pr-4"><?php print $stat['fields']; ?></td>
                                <td class="py-2.5 text-right font-medium"><?php print $stat['count']; ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-semibold">Last registered users</h2>
            </div>
            <div class="p-5 space-y-2 text-sm">
                <?php if (empty($users)) { ?>
                    <p class="text-gray-500 dark:text-gray-400">No users yet.</p>
                <?php } ?>
                <?php foreach ($users as $user) { ?>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-300"><?php print strtoupper(substr($user['username'] ?? '?', 0, 1)); ?></span>
                        <span class="text-gray-500 dark:text-gray-400">#<?php print $user['_id']; ?></span>
                        <span class="font-medium"><?php print htmlspecialchars($user['username'] ?? ''); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Config editor -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 h-fit">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="font-semibold">Stores Configuration</h2>
        </div>
        <form method="post">
            <div class="p-5">
                <textarea id="editor" class="json-editor w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="config_file"><?php print htmlspecialchars($_POST['config_file'] ?? $json); ?></textarea>

                <?php if ($msg) { ?>
                    <div class="mt-3 px-4 py-2 rounded-lg bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm"><?php print $msg; ?></div>
                <?php } ?>
                <?php if ($backupMsg) { ?>
                    <div class="mt-3 px-4 py-2 rounded-lg bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-sm"><?php print $backupMsg; ?></div>
                <?php } ?>

                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                    <button name="update_config" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors"><?php $core->_('Update'); ?></button>
                    <a href="?backup" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <?php $core->_('create_backup'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
