<?php

namespace SleekDBVCMS\Interfaces;

interface AuthenticationInterface
{
    public function login(string $username, string $password): bool;
    public function logout(): void;
    public function isLoggedIn(): bool;
    public function getCurrentUser(): ?array;
}
