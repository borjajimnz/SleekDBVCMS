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
    ];

    private array $config;
    private string $storesFilePath;

    public function __construct(array $config, string $storesFilePath)
    {
        $this->config = $config;
        $this->storesFilePath = $storesFilePath;
        $this->loadStores();
        $this->enforceProtectedStores();
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
