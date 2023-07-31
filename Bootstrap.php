<?php

require_once 'Core.php';

$curDir = dirname(__FILE__);

// Prevent to forge Config
if (!file_exists($curDir . '/Config.php')) {
    die('Error - Place "Config.php" in the same level as "Core.php".');
}

// Prevent to forge SleekDB
if (!file_exists($curDir . '/vendor/autoload.php')) {
    die('Error - Did you /composer install? <br> We cant\'t find "autoload.php" file inside "vendor" directory');
}

require_once $curDir . '/Config.php';
require_once $curDir . '/vendor/autoload.php';

// If public path is not defined or not exists, error
if (!isset($config['public_path'])) {
    die('Define the "public_path" in your Config file.');
}

if (!file_exists($config['public_path'])) {
    die('The "public_path" does not exists, define it your Config file.');
}


// Create storage path for data storage.
if (!is_dir($curDir . '/storage')) {
    mkdir($curDir . '/storage', 0777);
    if (!is_dir($curDir . '/storage/public')) mkdir($curDir . '/storage/public', 0777);
}

// Create storage path for data backups.
if (!is_dir($curDir . '/backups')) {
    mkdir($curDir . '/backups', 0777);
}

// Create data path for database storage.
if (!is_dir($curDir . '/storage/stores')) mkdir($curDir . '/storage/stores', 0777);

// Under windows, no symlink so we need to create Storage folder instead.
if (!is_dir($config['public_path'] . '/storage')) {
    if (!@symlink($curDir . '/storage/public', $config['public_path'] . '/storage')) {
        mkdir($config['public_path'] . '/storage', 0777);
    }
}

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

session_start();
$cms = new \Core\Core($config);
$database = $cms->database;

if ($cms->setup) {
    die('Needs a setup file.');
}
