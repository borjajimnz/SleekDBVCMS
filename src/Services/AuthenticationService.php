<?php

namespace SleekDBVCMS\Services;

use SleekDBVCMS\Interfaces\AuthenticationInterface;
use SleekDBVCMS\Interfaces\DatabaseInterface;

class AuthenticationService implements AuthenticationInterface
{
    private DatabaseInterface $database;
    private array $sessionData;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
        $this->sessionData = &$_SESSION;
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->database->store('users')
            ->findOneBy(['username', '=', $username]);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        $this->sessionData['logged'] = $user;
        return true;
    }

    public function logout(): void
    {
        unset($this->sessionData['logged']);
    }

    public function isLoggedIn(): bool
    {
        return isset($this->sessionData['logged']) && !empty($this->sessionData['logged']);
    }

    public function getCurrentUser(): ?array
    {
        return $this->sessionData['logged'] ?? null;
    }
}
