<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Api;

use Perspective\NovaposhtaCatalog\Api\Data\PackageInterface;

interface BoxManagementInterface
{
    public function getById(int $id): PackageInterface;

    public function save(array $data, ?int $id = null): PackageInterface;

    public function duplicate(int $id, array $data = []): PackageInterface;

    public function delete(int $id): void;

    public function setEnabled(int $id, bool $enabled): void;

    public function getDuplicateData(int $id): array;
}
