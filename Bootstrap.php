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

// Seed default module templates (one per supported type) into the modules
// store. Templates are pure configuration: a title, a type, and the schema of
// fields they expose (see ModulesType::typeFields()). They never carry values
// — per-page instances start empty and are filled in the page editor. Seeding
// is idempotent by title so existing installs pick up new templates without
// duplicating the ones they already have.
$cms->getDatabase()->store('modules');
$defaultModules = [
    ['title' => 'Hero Bienvenida', 'type' => 'hero', 'fields' => json_encode(['title', 'image', 'subtitle', 'cta_text', 'cta_url'])],
    ['title' => 'Texto de presentación', 'type' => 'text', 'fields' => json_encode(['html'])],
    ['title' => 'Últimos posts', 'type' => 'store_list', 'fields' => json_encode(['title', 'store', 'limit'])],
    ['title' => 'Post destacado', 'type' => 'store_item', 'fields' => json_encode(['title', 'store', 'item_id'])],
    ['title' => 'HTML libre', 'type' => 'html', 'fields' => json_encode(['html'])],
    ['title' => 'Formulario de contacto', 'type' => 'lead_form', 'fields' => json_encode(['title', 'form_id'])],
    ['title' => 'Llamada a la acción', 'type' => 'cta', 'fields' => json_encode(['title', 'subtitle', 'image', 'cta_text', 'cta_url'])],
    ['title' => 'Texto + Imagen', 'type' => 'split', 'fields' => json_encode(['title', 'text', 'image', 'image_position', 'cta_text', 'cta_url'])],
    ['title' => 'Características', 'type' => 'features', 'fields' => json_encode(['title', 'subtitle', 'features'])],
    ['title' => 'Cifras clave', 'type' => 'stats', 'fields' => json_encode(['title', 'stats'])],
    ['title' => 'Testimonios', 'type' => 'testimonials', 'fields' => json_encode(['title', 'subtitle', 'testimonials'])],
    ['title' => 'Preguntas frecuentes', 'type' => 'faq', 'fields' => json_encode(['title', 'subtitle', 'faq'])],
    ['title' => 'Planes de precios', 'type' => 'pricing', 'fields' => json_encode(['title', 'subtitle', 'pricing'])],
    ['title' => 'Confían en nosotros', 'type' => 'logos', 'fields' => json_encode(['title', 'logos'])],
    ['title' => 'Video promocional', 'type' => 'video', 'fields' => json_encode(['title', 'subtitle', 'video_url', 'poster'])],
];

$existingTitles = [];
foreach ($cms->getDatabase()->findAll('modules') as $existing) {
    $existingTitles[trim((string)($existing['title'] ?? ''))] = true;
}
foreach ($defaultModules as $module) {
    $title = trim((string)($module['title'] ?? ''));
    if ($title !== '' && isset($existingTitles[$title])) {
        continue;
    }
    $inserted = $cms->getDatabase()->insert('modules', $module);
    if (is_array($inserted)) {
        $existingTitles[trim((string)($inserted['title'] ?? $title))] = true;
    }
}
