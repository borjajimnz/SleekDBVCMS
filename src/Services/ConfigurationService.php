<?php

namespace SleekDBVCMS\Services;

class ConfigurationService
{
    // Stores that are required by the system and cannot be removed.
    public const PROTECTED_STORES = ['users'];

    // Store definitions that are always enforced.
    private const DEFAULT_PAGES_DEF = [
        'title' => 'text',
        'slug' => 'text',
        'published' => 'checkbox',
        'show_in_menu' => 'checkbox',
        'menu_order' => 'number',
        'is_home' => 'checkbox',
        'seo_title' => 'text',
        'seo_description' => 'textarea',
        'modules' => 'modules',
    ];

    // Fields of a module template record (the "modules" collection).
    // A lead_form module references a form template from the "forms" store
    // via `form_id` (like store_item references `item_id`).
    private const DEFAULT_MODULES_DEF = [
        'title' => 'text',
        'type' => 'select',
        'subtitle' => 'text',
        'image' => 'image',
        'cta_text' => 'text',
        'cta_url' => 'url',
        'html' => 'rich_textarea',
        'store' => 'select',
        'limit' => 'number',
        'item_id' => 'number',
        'form_id' => 'select',
    ];

    // Typical blog post record (the "posts" collection).
    private const DEFAULT_POSTS_DEF = [
        'title' => 'text',
        'slug' => 'text',
        'resume' => 'textarea',
        'body' => 'rich_textarea',
        'image' => 'image',
        'category' => [
            'join' => [
                'key' => 'category',
                'foreing_table' => 'categories',
                'foreing_key' => '_id',
                'foreing_display' => ['name'],
            ],
        ],
        'tags' => 'text',
        'author' => 'text',
        'published' => 'checkbox',
        'published_at' => 'datetime',
        'seo_title' => 'text',
        'seo_description' => 'textarea',
    ];

    // Typical blog category record (the "categories" collection).
    private const DEFAULT_CATEGORIES_DEF = [
        'name' => 'text',
        'slug' => 'text',
        'description' => 'textarea',
        'image' => 'image',
        'order' => 'number',
    ];

    // SEO redirect rules (the "redirects" collection). System store, records
    // are freely editable/deletable from the CMS.
    private const DEFAULT_REDIRECTS_DEF = [
        'source' => 'text',
        'target' => 'text',
        'code' => 'select',
        'enabled' => 'checkbox',
    ];

    // Leads submitted through lead_form modules. System store, records are
    // freely editable/deletable from the CMS.
    private const DEFAULT_LEADS_DEF = [
        'form' => 'text',
        'name' => 'text',
        'email' => 'email',
        'phone' => 'text',
        'company' => 'text',
        'message' => 'textarea',
        'page' => 'text',
        'payload' => 'textarea',
        'created' => 'datetime',
    ];

    // Form templates (the "forms" collection). System store, records are
    // freely editable/deletable from the CMS. A lead_form module references
    // one of these templates by _id; submissions are stored in "leads".
    private const DEFAULT_FORMS_DEF = [
        'title' => 'text',
        'subtitle' => 'text',
        'fields' => 'form_fields',
        'notify_to' => 'text',
        'notify_cc' => 'text',
        'button_text' => 'text',
        'success_message' => 'text',
    ];

    private array $config;
    private string $storesFilePath;
    private string $settingsFilePath;
    private array $settings = [];

    // Default site settings editable from the CMS dashboard.
    private const DEFAULT_SETTINGS = [
        'site_name' => 'My Sleek Site',
        'tagline' => '',
        'blog_enabled' => true,
        // SMTP notification settings (used by lead_form modules to email leads).
        // When smtp_enabled is off, leads are only stored (no email is sent).
        'smtp_enabled' => false,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls', // tls | ssl | none
        'smtp_from_email' => '',
        'smtp_from_name' => '',
    ];

    public function __construct(array $config, string $storesFilePath, string $settingsFilePath)
    {
        $this->config = $config;
        $this->storesFilePath = $storesFilePath;
        $this->settingsFilePath = $settingsFilePath;
        $this->loadStores();
        $this->enforceProtectedStores();
        $this->loadSettings();
    }

    private function loadStores(): void
    {
        $stores = [];
        if (file_exists($this->storesFilePath)) {
            $content = file_get_contents($this->storesFilePath);
            if ($content !== false) {
                $stores = json_decode($content, true) ?: [];
            }
        }
        $this->config['stores'] = $stores;
    }

    private function loadSettings(): void
    {
        $settings = [];
        if (file_exists($this->settingsFilePath)) {
            $content = file_get_contents($this->settingsFilePath);
            if ($content !== false) {
                $settings = json_decode($content, true) ?: [];
            }
        }
        $this->settings = array_merge(self::DEFAULT_SETTINGS, $settings);
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    // Whether the blog content type (posts/categories) is enabled site-wide.
    public function isBlogEnabled(): bool
    {
        return !empty($this->settings['blog_enabled']);
    }

    // Stores that should be visible in the CMS sidebar/dashboard given the settings.
    // When the blog is disabled, posts/categories are hidden from the admin too.
    public function getVisibleStores(): array
    {
        $stores = $this->getStores();
        if ($this->isBlogEnabled()) {
            return $stores;
        }
        return array_filter($stores, function ($name) {
            return !in_array($name, ['posts', 'categories'], true);
        }, ARRAY_FILTER_USE_KEY);
    }

    // True if a store should be reachable in the admin given current settings.
    public function isStoreVisible(string $name): bool
    {
        if ($this->isBlogEnabled()) {
            return true;
        }
        return !in_array($name, ['posts', 'categories'], true);
    }

    public function saveSettingsFromJson(string $json): bool
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        // Only persist known keys so arbitrary data can't pollute the file.
        $clean = [];
        foreach (self::DEFAULT_SETTINGS as $key => $default) {
            $clean[$key] = $decoded[$key] ?? $default;
            if (is_bool($default)) {
                $clean[$key] = (bool)$clean[$key];
            } else {
                $clean[$key] = (string)$clean[$key];
            }
        }
        if (file_put_contents($this->settingsFilePath, json_encode($clean, JSON_PRETTY_PRINT)) === false) {
            return false;
        }
        $this->settings = $clean;
        return true;
    }

    // Re-merge protected stores so they always exist in the running config,
    // even if someone edits .default_stores by hand.
    private function enforceProtectedStores(): void
    {
        $stores = $this->config['stores'] ?? [];
        $stores['users'] = $stores['users'] ?? [
            'username' => 'text',
            'email' => 'email',
            'password' => 'password',
            'created' => 'datetime',
        ];
        $stores['pages'] = $stores['pages'] ?? self::DEFAULT_PAGES_DEF;
        $stores['modules'] = $stores['modules'] ?? self::DEFAULT_MODULES_DEF;
        $stores['posts'] = $stores['posts'] ?? self::DEFAULT_POSTS_DEF;
        $stores['categories'] = $stores['categories'] ?? self::DEFAULT_CATEGORIES_DEF;
        $stores['redirects'] = $stores['redirects'] ?? self::DEFAULT_REDIRECTS_DEF;
        $stores['leads'] = $stores['leads'] ?? self::DEFAULT_LEADS_DEF;
        $stores['forms'] = $stores['forms'] ?? self::DEFAULT_FORMS_DEF;
        $this->config['stores'] = $stores;
    }

    public function isProtected(string $storeName): bool
    {
        return in_array($storeName, self::PROTECTED_STORES, true);
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function getStores(): array
    {
        return $this->config['stores'] ?? [];
    }

    public function getStore(string $name): array
    {
        return $this->config['stores'][$name] ?? [];
    }

    public function saveStoresFromJson(string $json): bool
    {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Protected stores cannot be removed from the config.
        $stores = $decoded['stores'] ?? $decoded;
        if (is_array($stores)) {
            $stores['users'] = $stores['users'] ?? [
                'username' => 'text',
                'email' => 'email',
                'password' => 'password',
                'created' => 'datetime',
            ];
            $stores['pages'] = $stores['pages'] ?? self::DEFAULT_PAGES_DEF;
            $stores['modules'] = $stores['modules'] ?? self::DEFAULT_MODULES_DEF;
            $stores['posts'] = $stores['posts'] ?? self::DEFAULT_POSTS_DEF;
            $stores['categories'] = $stores['categories'] ?? self::DEFAULT_CATEGORIES_DEF;
            $stores['redirects'] = $stores['redirects'] ?? self::DEFAULT_REDIRECTS_DEF;
            $stores['leads'] = $stores['leads'] ?? self::DEFAULT_LEADS_DEF;
            $stores['forms'] = $stores['forms'] ?? self::DEFAULT_FORMS_DEF;
            $decoded['stores'] = $stores;
        }

        if (file_put_contents($this->storesFilePath, json_encode($decoded, JSON_PRETTY_PRINT)) === false) {
            return false;
        }
        $this->config['stores'] = $decoded['stores'] ?? $stores;
        $this->enforceProtectedStores();
        return true;
    }

    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    public function save(): bool
    {
        return file_put_contents(
            $this->storesFilePath,
            json_encode($this->config['stores'] ?? [], JSON_PRETTY_PRINT)
        ) !== false;
    }
}
