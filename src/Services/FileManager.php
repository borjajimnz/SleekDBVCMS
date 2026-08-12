<?php

namespace SleekDBVCMS\Services;

class FileManager
{
    private string $rootPath;
    private string $publicPath;
    private array $allowedExtensions;
    private int $imageMaxSide;
    private int $imageQuality;

    public function __construct(string $rootPath, string $publicPath, array $allowedExtensions, int $imageMaxSide = 1920, int $imageQuality = 80)
    {
        $this->rootPath = $rootPath;
        $this->publicPath = $publicPath;
        $this->allowedExtensions = $allowedExtensions;
        $this->imageMaxSide = $imageMaxSide;
        $this->imageQuality = $imageQuality;
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

        // Optimize raster images: downscale + convert to WebP.
        $optimized = $this->optimizeImage($fullPath, $file[$fieldName]['type']);
        if ($optimized !== null) {
            $fullPath = $optimized;
            $fileName = basename($optimized);
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

    /**
     * Persist a base64 data URI (e.g. an image pasted/uploaded into a module)
     * as an optimized file under storage/public. Returns the public URL or
     * null when the value is not a supported image.
     */
    public function uploadDataUri(string $dataUri): ?string
    {
        if (!preg_match('#^data:(image/[a-z0-9.+-]+);base64,(.+)$#s', $dataUri, $m)) {
            return null;
        }

        $mime = strtolower($m[1]);
        $extension = $this->getExtension($mime);
        if (!$extension) {
            return null;
        }

        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $storageDir = $this->rootPath . '/storage/public/' . date('FY');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $fileName = md5($binary) . $extension;
        $fullPath = $storageDir . '/' . $fileName;

        if (file_exists($fullPath)) {
            return '/storage/' . date('FY') . '/' . $fileName;
        }

        if (file_put_contents($fullPath, $binary) === false) {
            return null;
        }

        // Optimize raster images: downscale + convert to WebP.
        $optimized = $this->optimizeImage($fullPath, $mime);
        if ($optimized !== null) {
            $fullPath = $optimized;
            $fileName = basename($optimized);
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

        return '/storage/' . date('FY') . '/' . $fileName;
    }

    /**
     * Downscale raster images larger than imageMaxSide and convert them to
     * WebP. Returns the new path when processed, null when the file is left
     * as-is (not a supported image or conversion failed).
     */
    private function optimizeImage(string $fullPath, string $mime): ?string
    {
        $loaders = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/gif' => 'imagecreatefromgif',
            'image/webp' => 'imagecreatefromwebp',
        ];
        if (!isset($loaders[$mime]) || !function_exists('imagewebp')) {
            return null;
        }

        $src = @$loaders[$mime]($fullPath);
        if (!$src) {
            return null;
        }

        // Apply EXIF orientation (phone photos) before resizing.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($fullPath)['Orientation'] ?? 0;
            if (in_array($orientation, [3, 6, 8], true)) {
                $angle = [3 => 180, 6 => 90, 8 => -90][$orientation];
                $rotated = imagerotate($src, $angle, 0);
                if ($rotated) {
                    imagedestroy($src);
                    $src = $rotated;
                }
            }
        }

        // Downscale when the longest side exceeds the configured maximum.
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $scale = min(1, $this->imageMaxSide / max($srcW, $srcH));
        if ($scale < 1) {
            $dstW = (int)round($srcW * $scale);
            $dstH = (int)round($srcH * $scale);
            $dst = imagecreatetruecolor($dstW, $dstH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
            imagedestroy($src);
            $src = $dst;
        }

        $dir = dirname($fullPath);
        $webpFull = $dir . '/' . pathinfo($fullPath, PATHINFO_FILENAME) . '.webp';
        $ok = imagewebp($src, $webpFull, $this->imageQuality);
        imagedestroy($src);

        if (!$ok) {
            return null;
        }

        @unlink($fullPath);
        return $webpFull;
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
