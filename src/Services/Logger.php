<?php

namespace SleekDBVCMS\Services;

class Logger
{
    private ?string $logFile;

    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile;
    }

    public function log(string $message): void
    {
        if (!$this->logFile) return;
        @file_put_contents(
            $this->logFile,
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function logException($exception): bool
    {
        $this->log(
            'FATAL ' . get_class($exception) . ': ' . $exception->getMessage()
            . ' in ' . $exception->getFile() . ':' . $exception->getLine()
            . PHP_EOL . $exception->getTraceAsString()
        );
        return false;
    }

    public function logError(int $severity, string $message, string $file, int $line): bool
    {
        if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) return false;
        if (error_reporting() === 0) return false;
        $this->log('ERROR: ' . $message . ' in ' . $file . ':' . $line);
        return false;
    }

    public function registerHandlers(): void
    {
        set_exception_handler([$this, 'logException']);
        set_error_handler([$this, 'logError']);
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }
}
