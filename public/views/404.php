<?php
/** @var array $frontConfig */
/** @var array $menuStores */
/** @var array $stores */

$pageTitle = 'Not found';
ob_start();
?>
<div class="text-center py-20">
    <h1 class="text-6xl font-bold text-gray-300 dark:text-gray-700">404</h1>
    <p class="mt-4 text-gray-600 dark:text-gray-400">The page you are looking for does not exist.</p>
    <a href="/" class="mt-6 inline-block px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">Back to home</a>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/layout.php';
