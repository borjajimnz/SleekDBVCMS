<?php
/** @var \SleekDBVCMS\Core $core */
/** @var string $content */

$current = $_GET['p'] ?? null;
$stores = $core->getConfig()->getStores();

// Sidebar sections: content stores vs system stores.
$contentStores = [];
$systemStores = [];
foreach ($stores as $name => $def) {
    if (in_array($name, ['pages', 'modules', 'users', 'roles'], true)) {
        $systemStores[$name] = $def;
    } else {
        $contentStores[$name] = $def;
    }
}

function cms_store_link(string $name, ?string $current): string
{
    $active = $current === $name;
    return '<a href="?p=' . urlencode($name) . '"'
        . ' class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm '
        . ($active ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800') . '">'
        . '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>'
        . htmlspecialchars($name) . '</a>';
}
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
        // Apply theme before render to avoid flash
        (function () {
            var stored = localStorage.getItem('cms-theme');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        .json-editor { min-height: 600px; font-family: ui-monospace, monospace; font-size: 0.8rem; }
    </style>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
    <div class="flex min-h-full">
        <!-- Sidebar overlay (mobile) -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar(false)"></div>

        <!-- Sidebar -->
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transition-transform duration-200 lg:translate-x-0 lg:static lg:z-auto bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col">
            <div class="flex items-center justify-between px-4 h-16 border-b border-gray-200 dark:border-gray-800">
                <a href="index.php" class="font-bold text-lg truncate"><?php print $core->getConfig()->get('app_name', 'SleekDBVCMS'); ?></a>
                <button onclick="toggleSidebar(false)" class="lg:hidden text-gray-500 dark:text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <a href="index.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php print $current === null ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <?php $core->_('dashboard'); ?>
                </a>
                <a href="../" target="_blank" rel="noopener"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-8.5M19 3h-4M19 3v4M19 3l-8.5 8.5"/></svg>
                    <?php $core->_('View site'); ?>
                </a>
                <?php if ($contentStores) { ?>
                    <div class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Contenido</div>
                    <?php foreach ($contentStores as $storek => $storev) { print cms_store_link($storek, $current); } ?>
                <?php } ?>
                <?php if ($systemStores) { ?>
                    <div class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Sistema</div>
                    <?php foreach ($systemStores as $storek => $storev) { print cms_store_link($storek, $current); } ?>
                <?php } ?>
            </nav>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-20 h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center gap-3 px-4">
                <button onclick="toggleSidebar(true)" class="lg:hidden p-2 -ml-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold truncate capitalize"><?php print $current !== null ? htmlspecialchars($current) : $core->_('dashboard'); ?></h1>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <?php if ($current !== null) { ?>
                        <form method="post" class="hidden sm:block">
                            <div class="relative">
                                <input name="search" type="text" value="<?php print htmlspecialchars($_POST['search'] ?? ''); ?>"
                                       placeholder="<?php $core->_('search'); ?>"
                                       class="pl-8 pr-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </form>
                    <?php } ?>
                    <button onclick="toggleTheme()" title="Toggle theme"
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                    <a href="?logout" class="flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span class="hidden sm:inline"><?php $core->_('Sign out'); ?></span>
                    </a>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <?php print $content; ?>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar(open) {
            var sb = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            if (open) {
                sb.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sb.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
        function toggleTheme() {
            var dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('cms-theme', dark ? 'dark' : 'light');
        }
    </script>
</body>
</html>
