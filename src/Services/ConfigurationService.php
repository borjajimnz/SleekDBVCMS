<?php

namespace SleekDBVCMS\Services;

class ConfigurationService
{
    private array $config;
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        if (file_exists($this->configPath)) {
            $this->config = require $this->configPath;
        } else {
            $this->config = $this->getDefaultConfig();
        }
    }

    private function getDefaultConfig(): array
    {
        return [
            'stores' => [
                'users' => [
                    'username' => 'text',
                    'password' => 'password',
                    'email' => 'email',
                    'created' => 'datetime',
                ]
            ],
            'upload_files_extensions_allowed' => [
                'image/jpeg' => 'jpg',
                'image/png' => 'png'
            ],
            'language' => 'en'
        ];
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    public function save(): bool
    {
        return file_put_contents(
            $this->configPath,
            '<?php return ' . var_export($this->config, true) . ';'
        ) !== false;
    }
}
