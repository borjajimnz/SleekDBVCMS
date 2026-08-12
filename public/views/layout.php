<?php
/** @var array $frontConfig */
/** @var array $menuStores */
/** @var array $stores */
/** @var array $navPages */
/** @var string|null $storeName */
/** @var string $pageTitle */
/** @var string $metaDescription */
/** @var string $pageSlug */

$currentPage = $pageSlug ?? ($storeName !== null ? 'store-' . $storeName : null);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php print front_escape($pageTitle ?? $frontConfig['site_name']); ?> — <?php print front_escape($frontConfig['site_name']); ?></title>
    <?php if (!empty($metaDescription)) { ?>
        <meta name="description" content="<?php print front_escape($metaDescription); ?>">
    <?php } ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script>
        (function () {
            var stored = localStorage.getItem('front-theme');
            var pref = '<?php print $frontConfig['theme']; ?>';
            if (stored === 'dark' || (pref === 'dark' && !stored) || (!stored && pref === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        .prose-html img { max-width: 100%; height: auto; border-radius: 0.5rem; }
        .prose-html a { color: #2563eb; text-decoration: underline; }
        .dark .prose-html a { color: #60a5fa; }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased flex flex-col">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="h-16 flex items-center gap-4">
                <a href="/" class="shrink-0">
                    <span class="text-lg font-bold"><?php print front_escape($frontConfig['site_name']); ?></span>
                </a>
                <?php if (!empty($frontConfig['tagline'])) { ?>
                    <span class="hidden md:inline text-sm text-gray-500 dark:text-gray-400 truncate"><?php print front_escape($frontConfig['tagline']); ?></span>
                <?php } ?>
                <nav class="ml-auto flex items-center gap-1 overflow-x-auto">
                    <?php foreach ($navPages as $nav) {
                        $isActive = $currentPage === ($nav['slug'] ?? '');
                        ?>
                        <a href="/?page=<?php print urlencode($nav['slug'] ?? ''); ?>"
                           class="px-3 py-1.5 rounded-lg text-sm whitespace-nowrap <?php print $isActive ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>"><?php print front_escape($nav['title'] ?? 'Page'); ?></a>
                    <?php } ?>
                    <button onclick="toggleTheme()" title="Toggle theme"
                            class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- Custom header block -->
    <?php if (!empty($frontConfig['header_html'])) { ?>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 pt-6"><?php print $frontConfig['header_html']; ?></div>
    <?php } ?>

    <!-- Main -->
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 py-8">
        <?php print $content; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center gap-2 justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400"><?php print front_escape($frontConfig['site_name']); ?></span>
            <span class="text-sm text-gray-500 dark:text-gray-400"><?php print $frontConfig['footer_html']; ?></span>
        </div>
    </footer>

    <script>
        function toggleTheme() {
            var dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('front-theme', dark ? 'dark' : 'light');
        }
    </script>
</body>
</html>
