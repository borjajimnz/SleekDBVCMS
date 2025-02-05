<?php

// Load default stores from JSON file
$defaultStoresFile = __DIR__ . '/.default_stores';
$defaultStores = [];

if (file_exists($defaultStoresFile)) {
    $jsonContent = file_get_contents($defaultStoresFile);
    if ($jsonContent !== false) {
        $defaultStores = json_decode($jsonContent, true) ?: [];
    }
}

// Define configuration array
$config = [
    'app_name' => 'SleekdbVCMS',
    'public_path' => dirname(__FILE__).'/public',
    'locale' => 'es',
    'stores' => $defaultStores,
    'upload_files_extensions_allowed' => [
        'image/jpeg' => 'jpeg',
        'image/png' => 'png',
        'text/xml' => 'xml',
    ],
    'options' => [
        'auto_cache' => false,
        'timeout' => 121
    ],
];
