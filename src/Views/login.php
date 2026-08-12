<?php
/** @var \SleekDBVCMS\Core $core */
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php print $core->getConfig()->get('app_name', 'SleekDBVCMS'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script>
        (function () {
            var stored = localStorage.getItem('cms-theme');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-950 flex items-center justify-center p-4 antialiased">
    <div class="w-full max-w-sm">
        <div class="flex justify-end mb-2">
            <button onclick="toggleTheme()" title="Toggle theme"
                    class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800">
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>
        <div class="bg-white dark:bg-gray-900 shadow-xl rounded-2xl p-8 border border-gray-200 dark:border-gray-800">
            <h2 class="text-2xl font-bold text-center mb-1"><?php $core->_('Login'); ?></h2>
            <div class="text-center text-sm text-gray-500 dark:text-gray-400 mb-6"><?php print $core->getConfig()->get('app_name', 'SleekDBVCMS'); ?></div>

            <?php if ($error) { ?>
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                    <?php print htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="post" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium mb-1">Username</label>
                    <input type="text" id="username" name="username" required="required"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <input type="password" id="password" name="password" required="required"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" name="login"
                        class="w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition-colors">
                    <?php $core->_('Log in'); ?>
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleTheme() {
            var dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('cms-theme', dark ? 'dark' : 'light');
        }
    </script>
</body>
</html>
