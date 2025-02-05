<?php

namespace SleekDBVCMS;

use SleekDBVCMS\Interfaces\DatabaseInterface;
use SleekDBVCMS\Interfaces\AuthenticationInterface;
use SleekDBVCMS\Services\ConfigurationService;
use SleekDBVCMS\Services\FileManager;
use SleekDBVCMS\Forms\FormBuilder;

class Core
{
    private DatabaseInterface $database;
    private AuthenticationInterface $auth;
    private ConfigurationService $config;
    private FileManager $fileManager;
    private FormBuilder $formBuilder;

    public function __construct(
        DatabaseInterface $database,
        AuthenticationInterface $auth,
        ConfigurationService $config,
        FileManager $fileManager,
        FormBuilder $formBuilder
    ) {
        $this->database = $database;
        $this->auth = $auth;
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->formBuilder = $formBuilder;
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
}
