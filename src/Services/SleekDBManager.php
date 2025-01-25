<?php

namespace SleekDBVCMS\Services;

use SleekDBVCMS\Interfaces\DatabaseInterface;
use SleekDB\Store;

class SleekDBManager implements DatabaseInterface
{
    private string $storePath;
    private array $options;

    public function __construct(string $storePath, array $options = [])
    {
        $this->storePath = $storePath;
        $this->options = $options;
    }

    public function store(string $storeName)
    {
        return new Store($storeName, $this->storePath, $this->options);
    }

    public function findById(string $storeName, int $id)
    {
        return $this->store($storeName)->findById($id);
    }

    public function insert(string $storeName, array $data)
    {
        return $this->store($storeName)->insert($data);
    }

    public function update(string $storeName, array $data)
    {
        return $this->store($storeName)->update($data);
    }

    public function delete(string $storeName, int $id)
    {
        return $this->store($storeName)->deleteById($id);
    }
}
