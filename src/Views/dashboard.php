<?php
/** @var \SleekDBVCMS\Core $core */
/** @var array $stats */
/** @var string $json */
/** @var string|null $msg */
/** @var string|null $backupMsg */
/** @var array $users */
/** @var array $settings */
/** @var string|null $settingsMsg */
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

        <!-- Site settings -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-semibold">Site Settings</h2>
            </div>
            <form method="post">
                <div class="p-5 space-y-4">
                    <div class="space-y-1.5">
                        <label for="site_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Website name</label>
                        <input id="site_name" name="site_name" type="text" value="<?php print htmlspecialchars($settings['site_name'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="space-y-1.5">
                        <label for="tagline" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tagline</label>
                        <input id="tagline" name="tagline" type="text" value="<?php print htmlspecialchars($settings['tagline'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="blog_enabled" value="0">
                        <input id="blog_enabled" name="blog_enabled" type="checkbox" value="1"
                               class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-blue-600 dark:bg-gray-800 focus:ring-blue-500"
                               <?php print !empty($settings['blog_enabled']) ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable blog (posts &amp; categories)</span>
                    </label>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold">Email notifications (lead forms)</h3>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="hidden" name="smtp_enabled" value="0">
                                <input id="smtp_enabled" name="smtp_enabled" type="checkbox" value="1"
                                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-blue-600 dark:bg-gray-800 focus:ring-blue-500"
                                       <?php print !empty($settings['smtp_enabled']) ? 'checked' : ''; ?>>
                                Enable SMTP
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="smtp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP host</label>
                                <input id="smtp_host" name="smtp_host" type="text" value="<?php print htmlspecialchars($settings['smtp_host'] ?? ''); ?>"
                                       placeholder="smtp.gmail.com" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="space-y-1.5">
                                <label for="smtp_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Port</label>
                                <input id="smtp_port" name="smtp_port" type="number" value="<?php print htmlspecialchars((string)($settings['smtp_port'] ?? 587)); ?>"
                                       class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="space-y-1.5">
                                <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                                <select id="smtp_encryption" name="smtp_encryption"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <?php foreach (['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL (implicit)', 'none' => 'None'] as $val => $label) { ?>
                                        <option value="<?php print $val; ?>" <?php print (($settings['smtp_encryption'] ?? 'tls') === $val) ? 'selected' : ''; ?>><?php print $label; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label for="smtp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                                <input id="smtp_username" name="smtp_username" type="text" value="<?php print htmlspecialchars($settings['smtp_username'] ?? ''); ?>" autocomplete="off"
                                       class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="space-y-1.5">
                                <label for="smtp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                <input id="smtp_password" name="smtp_password" type="password" value="<?php print htmlspecialchars($settings['smtp_password'] ?? ''); ?>" autocomplete="new-password"
                                       class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="space-y-1.5">
                                <label for="smtp_from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From email</label>
                                <input id="smtp_from_email" name="smtp_from_email" type="text" value="<?php print htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>"
                                       placeholder="no-reply@<?php print htmlspecialchars(parse_url('https://' . ($_SERVER['HTTP_HOST'] ?? 'example.com'), PHP_URL_HOST)); ?>"
                                       class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="space-y-1.5">
                                <label for="smtp_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From name</label>
                                <input id="smtp_from_name" name="smtp_from_name" type="text" value="<?php print htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>"
                                       placeholder="Website" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">When SMTP is off, lead form submissions are only stored in the <code>leads</code> collection (no email sent).</p>
                    </div>

                    <?php if ($settingsMsg) { ?>
                        <div class="px-4 py-2 rounded-lg bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm"><?php print $settingsMsg; ?></div>
                    <?php } ?>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button name="update_settings" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">Save settings</button>
                    </div>
                </div>
            </form>
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
