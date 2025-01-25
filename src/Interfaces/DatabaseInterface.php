<?php

namespace SleekDBVCMS\Interfaces;

interface DatabaseInterface
{
    public function store(string $storeName);
    public function findById(string $storeName, int $id);
    public function insert(string $storeName, array $data);
    public function update(string $storeName, array $data);
    public function delete(string $storeName, int $id);
}
