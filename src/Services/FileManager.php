<?php

namespace SleekDBVCMS\Services;

class FileManager
{
    private string $publicPath;
    private array $allowedExtensions;

    public function __construct(string $publicPath, array $allowedExtensions)
    {
        $this->publicPath = $publicPath;
        $this->allowedExtensions = $allowedExtensions;
    }

    public function uploadFile(array $file, string $destinationPath): ?string
    {
        if (!isset($file['type']) || !isset($this->allowedExtensions[$file['type']])) {
            return null;
        }

        $extension = $this->allowedExtensions[$file['type']];
        $fileName = md5($file['name']) . '.' . $extension;
        $fullPath = $this->publicPath . '/' . $destinationPath . '/' . $fileName;
        
        // Create directory if it doesn't exist
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return $destinationPath . '/' . $fileName;
        }

        return null;
    }

    public function deleteFile(string $path): bool
    {
        $fullPath = $this->publicPath . '/' . $path;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function fileExists(string $path): bool
    {
        return file_exists($this->publicPath . '/' . $path);
    }
}
