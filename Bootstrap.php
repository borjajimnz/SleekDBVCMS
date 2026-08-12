<?php

require_once 'vendor/autoload.php';

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

// Seed a default contact form template if the forms store is empty.
// lead_form modules reference these templates by _id; submissions go to "leads".
$cms->getDatabase()->store('forms');
$forms = $cms->getDatabase()->findAll('forms');
$contactFormId = 0;
if (empty($forms)) {
    $contactForm = $cms->getDatabase()->insert('forms', [
        'title' => 'Formulario de contacto',
        'subtitle' => 'Rellena el formulario y te responderemos lo antes posible.',
        'fields' => json_encode([
            ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['name' => 'phone', 'label' => 'Teléfono', 'type' => 'tel', 'required' => false],
            ['name' => 'company', 'label' => 'Empresa', 'type' => 'text', 'required' => false],
            ['name' => 'message', 'label' => 'Mensaje', 'type' => 'textarea', 'required' => true],
        ]),
        'notify_to' => '',
        'notify_cc' => '',
        'button_text' => 'Enviar',
        'success_message' => '¡Gracias! Tu mensaje ha sido enviado.',
    ]);
    $contactFormId = (int)($contactForm['_id'] ?? 0);
} else {
    $contactFormId = (int)($forms[0]['_id'] ?? 0);
}

// Seed default module templates (one per supported type) if the modules store is empty.
// Each record is a module template that can be added to a page via the module builder.
// The lead_form template references the contact form above via form_id.
$cms->getDatabase()->store('modules');
$modules = $cms->getDatabase()->findAll('modules');
if (empty($modules)) {
    $defaultModules = [
        [
            'title' => 'Hero Bienvenida',
            'type' => 'hero',
            'subtitle' => 'Bienvenido a nuestro sitio. Descubre todo lo que tenemos para ti.',
            'image' => '',
            'cta_text' => 'Ver más',
            'cta_url' => '',
            'html' => '',
            'store' => '',
            'limit' => '',
            'item_id' => '',
            'form_id' => '',
        ],
        [
            'title' => 'Texto de presentación',
            'type' => 'text',
            'subtitle' => '',
            'image' => '',
            'cta_text' => '',
            'cta_url' => '',
            'html' => '<h3>Quiénes somos</h3><p>Este es un texto de ejemplo. Cuenta tu historia aquí.</p>',
            'store' => '',
            'limit' => '',
            'item_id' => '',
            'form_id' => '',
        ],
        [
            'title' => 'Últimos posts',
            'type' => 'store_list',
            'subtitle' => '',
            'image' => '',
            'cta_text' => '',
            'cta_url' => '',
            'html' => '',
            'store' => 'posts',
            'limit' => '4',
            'item_id' => '',
            'form_id' => '',
        ],
        [
            'title' => 'Post destacado',
            'type' => 'store_item',
            'subtitle' => '',
            'image' => '',
            'cta_text' => '',
            'cta_url' => '',
            'html' => '',
            'store' => 'posts',
            'limit' => '',
            'item_id' => '',
            'form_id' => '',
        ],
        [
            'title' => 'HTML libre',
            'type' => 'html',
            'subtitle' => '',
            'image' => '',
            'cta_text' => '',
            'cta_url' => '',
            'html' => '<div class="grid grid-cols-3 gap-4">' .
                '<div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-center"><b>100+</b><p class="text-xs">clientes</p></div>' .
                '<div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-center"><b>24/7</b><p class="text-xs">soporte</p></div>' .
                '<div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-center"><b>10</b><p class="text-xs">años</p></div>' .
                '</div>',
            'store' => '',
            'limit' => '',
            'item_id' => '',
            'form_id' => '',
        ],
        [
            'title' => 'Formulario de contacto',
            'type' => 'lead_form',
            'subtitle' => 'Rellena el formulario y te responderemos lo antes posible.',
            'image' => '',
            'cta_text' => '',
            'cta_url' => '',
            'html' => '',
            'store' => '',
            'limit' => '',
            'item_id' => '',
            'form_id' => (string)$contactFormId,
        ],
    ];
    foreach ($defaultModules as $module) {
        $cms->getDatabase()->insert('modules', $module);
    }
}
