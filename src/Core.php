<?php

namespace SleekDBVCMS;

use SleekDBVCMS\Interfaces\DatabaseInterface;
use SleekDBVCMS\Interfaces\AuthenticationInterface;
use SleekDBVCMS\Services\ConfigurationService;
use SleekDBVCMS\Services\FileManager;
use SleekDBVCMS\Services\Logger;
use SleekDBVCMS\Services\BladeRenderer;
use SleekDBVCMS\Services\EmailService;
use SleekDBVCMS\Forms\FormBuilder;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Core
{
    private DatabaseInterface $database;
    private AuthenticationInterface $auth;
    private ConfigurationService $config;
    private FileManager $fileManager;
    private FormBuilder $formBuilder;
    private Logger $logger;
    private BladeRenderer $blade;
    private EmailService $email;
    private string $rootPath;
    private string $storagePath;
    private string $storePath;

    public function __construct(
        DatabaseInterface $database,
        AuthenticationInterface $auth,
        ConfigurationService $config,
        FileManager $fileManager,
        FormBuilder $formBuilder,
        Logger $logger,
        BladeRenderer $blade,
        EmailService $email,
        string $rootPath
    ) {
        $this->database = $database;
        $this->auth = $auth;
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->formBuilder = $formBuilder;
        $this->logger = $logger;
        $this->blade = $blade;
        $this->email = $email;
        $this->rootPath = $rootPath;
        $this->storagePath = $rootPath . '/storage';
        $this->storePath = $rootPath . '/storage/stores';
    }

    public function getDatabase(): DatabaseInterface
    {
        return $this->database;
    }

    public function getAuth(): AuthenticationInterface
    {
        return $this->auth;
    }

    public function getConfig(): ConfigurationService
    {
        return $this->config;
    }

    public function getFileManager(): FileManager
    {
        return $this->fileManager;
    }

    public function getFormBuilder(): FormBuilder
    {
        return $this->formBuilder;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getBlade(): BladeRenderer
    {
        return $this->blade;
    }

    public function getEmail(): EmailService
    {
        return $this->email;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function getStorePath(): string
    {
        return $this->storePath;
    }

    public function log(string $message): void
    {
        $this->logger->log($message);
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function redirect(string $url, $alert = null): void
    {
        header('Location: ' . $url);
        if ($alert !== null) {
            $_SESSION['notifications'] = $alert;
        }
        exit;
    }

    public function __(string $key): string
    {
        return $key;
    }

    public function _(string $key): void
    {
        print $this->__($key);
    }

    public function ensureStorageWritable(): void
    {
        umask(0);
        foreach (['storage', 'storage/public', 'storage/stores', 'storage/logs', 'storage/blade-cache', 'backups'] as $dir) {
            $path = $this->rootPath . '/' . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            @chmod($path, 0777);
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->storagePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            @chmod($file->getPathname(), 0777);
        }
    }
}
