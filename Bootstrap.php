<?php

require_once __DIR__ . '/vendor/autoload.php';

use SleekDBVCMS\Core;
use SleekDBVCMS\Controllers\AdminController;
use SleekDBVCMS\Services\ConfigurationService;
use SleekDBVCMS\Services\SleekDBManager;
use SleekDBVCMS\Services\AuthenticationService;
use SleekDBVCMS\Services\FileManager;
use SleekDBVCMS\Services\Logger;
use SleekDBVCMS\Services\BladeRenderer;
use SleekDBVCMS\Services\EmailService;
use SleekDBVCMS\Forms\FormBuilder;

$curDir = dirname(__FILE__);

// Prevent to forge Config
if (!file_exists($curDir . '/Config.php')) {
    die('Error - Place "Config.php" in the same level as "Core.php".');
}

// Prevent to forge SleekDB
if (!file_exists($curDir . '/vendor/autoload.php')) {
    die('Error - Did you /composer install? <br> We can\'t find "autoload.php" file inside "vendor" directory');
}

/**
 * Versioned URL for the compiled Tailwind CSS. The ?v= query (file mtime)
 * busts the browser cache only when the CSS is rebuilt, while /dist/ can
 * still be served with long-lived immutable cache headers.
 */
function cms_css_url(): string
{
    $file = dirname(__FILE__) . '/public/dist/tailwind.css';
    return '/dist/tailwind.css?v=' . (file_exists($file) ? (int)@filemtime($file) : 0);
}

$config = null;
require_once $curDir . '/Config.php';

// If public path is not defined or not exists, error
if (!isset($config['public_path'])) {
    die('Define the "public_path" in your Config file.');
}

if (!file_exists($config['public_path'])) {
    die('The "public_path" does not exists, define it your Config file.');
}

// Create storage paths.
foreach (['storage', 'storage/public', 'storage/stores', 'storage/logs', 'storage/blade-cache', 'backups'] as $dir) {
    if (!is_dir($curDir . '/' . $dir)) {
        @mkdir($curDir . '/' . $dir, 0777, true);
    }
}

// Under windows, no symlink so we need to create Storage folder instead.
$publicStorage = $config['public_path'] . '/storage';
if (!is_dir($publicStorage)) {
    // Prefer a RELATIVE symlink so the project is portable (the old code
    // linked to an absolute path, which breaks when the repo moves).
    if (!@symlink('../storage/public', $publicStorage)) {
        @mkdir($publicStorage, 0777, true);
    }
}

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

session_start();

// Boot services
umask(0);

$logger = new Logger($curDir . '/storage/logs/cms.log');
$logger->registerHandlers();

$database = new SleekDBManager($curDir . '/storage/stores', $config['options'] ?? []);
$auth = new AuthenticationService($database);
$configuration = new ConfigurationService($config, $curDir . '/.default_stores', $curDir . '/storage/settings.json');
$fileManager = new FileManager(
    $curDir,
    $config['public_path'],
    $config['upload_files_extensions_allowed'] ?? [],
    (int)($config['options']['image_max_side'] ?? 1920),
    (int)($config['options']['image_quality'] ?? 80)
);
$formBuilder = new FormBuilder();
$blade = new BladeRenderer($curDir . '/public/views', $curDir . '/storage/blade-cache');
$email = new EmailService($configuration->getSettings(), $logger);
$cms = new Core(
    $database,
    $auth,
    $configuration,
    $fileManager,
    $formBuilder,
    $logger,
    $blade,
    $email,
    $curDir
);

// Seed defaults only when storage/settings.json is missing (first boot).
if (!is_file($curDir . '/storage/settings.json')) {
    $cms->ensureStorageWritable();
    $cms->install();
}

// Load project-level hooks/overrides if present (user-owned, never overwritten
// by `bin/cms publish`). Hooks register actions/filters/custom pages/menus;
// labels.php returns a [key => string] map consumed by Core::__().
$hooksFile = $curDir . '/cms_hooks.php';
if (file_exists($hooksFile)) {
    require $hooksFile;
}
$labelsFile = $curDir . '/admin/labels.php';
if (file_exists($labelsFile)) {
    $labels = require $labelsFile;
    if (is_array($labels)) {
        $cms->setTranslations($labels);
    }
}