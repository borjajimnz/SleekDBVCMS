<?php

namespace SleekDBVCMS\Services;

class FileManager
{
    private string $rootPath;
    private string $publicPath;
    private array $allowedExtensions;

    public function __construct(string $rootPath, string $publicPath, array $allowedExtensions)
    {
        $this->rootPath = $rootPath;
        $this->publicPath = $publicPath;
        $this->allowedExtensions = $allowedExtensions;
    }

    public function getExtension(string $mimeType): ?string
    {
        if (isset($this->allowedExtensions[$mimeType])) {
            return '.' . $this->allowedExtensions[$mimeType];
        }
        return null;
    }

    public function uploadFile(array $file, string $fieldName): ?string
    {
        if (!isset($file[$fieldName]['name'], $file[$fieldName]['type'], $file[$fieldName]['tmp_name'])) {
            return null;
        }

        $extension = $this->getExtension($file[$fieldName]['type']);
        if (!$extension) {
            return null;
        }

        $storageDir = $this->rootPath . '/storage/public/' . date('FY');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $fileName = md5($file[$fieldName]['name']) . $extension;
        $storedPath = '/storage/public/' . date('FY') . '/' . $fileName;
        $fullPath = $this->rootPath . $storedPath;

        if (!move_uploaded_file($file[$fieldName]['tmp_name'], $fullPath)) {
            return null;
        }

        // If storage is not a symlink in public, mirror the file there.
        $publicStorage = $this->publicPath . '/storage';
        if (!is_link($publicStorage) && !is_dir($publicStorage)) {
            $publicDir = $publicStorage . '/' . date('FY');
            if (!is_dir($publicDir)) {
                mkdir($publicDir, 0777, true);
            }
            copy($fullPath, $publicDir . '/' . $fileName);
        }

        // Public URL for the uploaded file.
        return '/storage/' . date('FY') . '/' . $fileName;
    }

    public function deleteFile(string $path): bool
    {
        $fullPath = $this->rootPath . $path;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function fileExists(string $path): bool
    {
        return file_exists($this->rootPath . $path);
    }
}
