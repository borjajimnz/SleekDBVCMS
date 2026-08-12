<?php

require_once 'vendor/autoload.php';

use SleekDBVCMS\Core;
use SleekDBVCMS\Controllers\AdminController;
use SleekDBVCMS\Services\ConfigurationService;
use SleekDBVCMS\Services\SleekDBManager;
use SleekDBVCMS\Services\AuthenticationService;
use SleekDBVCMS\Services\FileManager;
use SleekDBVCMS\Services\Logger;
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
foreach (['storage', 'storage/public', 'storage/stores', 'storage/logs', 'backups'] as $dir) {
    if (!is_dir($curDir . '/' . $dir)) {
        @mkdir($curDir . '/' . $dir, 0777, true);
    }
}

// Under windows, no symlink so we need to create Storage folder instead.
$publicStorage = $config['public_path'] . '/storage';
if (!is_dir($publicStorage)) {
    if (!@symlink($curDir . '/storage/public', $publicStorage)) {
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
$configuration = new ConfigurationService($config, $curDir . '/.default_stores');
$fileManager = new FileManager($curDir, $config['public_path'], $config['upload_files_extensions_allowed'] ?? []);
$formBuilder = new FormBuilder();

$cms = new Core(
    $database,
    $auth,
    $configuration,
    $fileManager,
    $formBuilder,
    $logger,
    $curDir
);

// Ensure storage is writable by the web server
$cms->ensureStorageWritable();

// Seed the default admin user if the users store is empty
$cms->getDatabase()->store('users');
$users = $cms->getDatabase()->findAll('users');
if (empty($users)) {
    $cms->getDatabase()->insert('users', [
        'username' => 'admin',
        'email' => 'admin@admin.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'created' => date('Y-m-d H:i:s'),
    ]);
}
