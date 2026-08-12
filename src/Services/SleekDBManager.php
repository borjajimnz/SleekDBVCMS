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

    public function store(string $storeName): Store
    {
        return new Store($storeName, $this->storePath, $this->options);
    }

    public function findById(string $storeName, int $id)
    {
        return $this->store($storeName)->findById($id);
    }

    public function findAll(string $storeName, array $orderBy = null, int $limit = null, int $offset = null): array
    {
        return $this->store($storeName)->findAll($orderBy, $limit, $offset);
    }

    public function findOneBy(string $storeName, array $criteria)
    {
        return $this->store($storeName)->findOneBy($criteria);
    }

    public function insert(string $storeName, array $data): array
    {
        return $this->store($storeName)->insert($data);
    }

    public function update(string $storeName, array $data): bool
    {
        return $this->store($storeName)->update($data);
    }

    public function delete(string $storeName, int $id): bool
    {
        return $this->store($storeName)->deleteById($id);
    }
}
