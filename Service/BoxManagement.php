<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Perspective\NovaposhtaCatalog\Api\Data\PackageInterface;
use Perspective\NovaposhtaCatalog\Model\Package\PackageFactory;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package as PackageResource;
use Perspective\NovaposhtaCatalog\Model\Update\DirectorySnapshot;
use Perspective\NovaposhtaShipping\Api\BoxManagementInterface;

class BoxManagement implements BoxManagementInterface
{
    public const LOCK_NAME = 'perspective_novaposhta_packages_sync';

    public function __construct(
        private PackageFactory $packageFactory,
        private PackageResource $resource,
        private DirectorySnapshot $snapshot,
        private BoxDataValidator $validator
    ) {
    }

    public function getById(int $id): PackageInterface
    {
        $package = $this->packageFactory->create();
        $this->resource->load($package, $id);
        if (!$package->getId()) {
            throw new NoSuchEntityException(__('The requested box does not exist.'));
        }
        return $package;
    }

    public function save(array $data, ?int $id = null): PackageInterface
    {
        return $this->snapshot->withLock(self::LOCK_NAME, function () use ($data, $id) {
            $package = $id === null ? $this->packageFactory->create() : $this->getById($id);
            if ($id !== null && $package->getSource() !== 'custom') {
                throw new LocalizedException(__('Only custom boxes can be edited.'));
            }
            $values = $this->validator->validate($data);
            if ($id === null) {
                $package->addData([
                    'source' => 'custom',
                    'ref' => 'custom-' . bin2hex(random_bytes(16)),
                    'is_active' => 1,
                ]);
            }
            $package->addData($values);
            $this->resource->save($package);
            return $package;
        });
    }

    public function getDuplicateData(int $id): array
    {
        $package = $this->getById($id);
        return [
            'description_ua' => $package->getDescriptionUa(),
            'length' => $package->getLength(),
            'width' => $package->getWidth(),
            'height' => $package->getHeight(),
            'max_weight' => $package->getMaxWeight() ?? $package->getVolumetricWeight(),
            'inner_length' => $package->getInnerLength(),
            'inner_width' => $package->getInnerWidth(),
            'inner_height' => $package->getInnerHeight(),
            'empty_weight' => $package->getEmptyWeight(),
            'boxpacker_enabled' => $package->getBoxpackerEnabled(),
        ];
    }

    public function duplicate(int $id, array $data = []): PackageInterface
    {
        return $this->snapshot->withLock(self::LOCK_NAME, function () use ($id, $data) {
            return $this->save(array_replace($this->getDuplicateData($id), $data));
        });
    }

    public function delete(int $id): void
    {
        $this->snapshot->withLock(self::LOCK_NAME, function () use ($id) {
            $package = $this->getById($id);
            if ($package->getSource() !== 'custom') {
                throw new LocalizedException(__('Only custom boxes can be deleted.'));
            }
            $this->resource->delete($package);
        });
    }

    public function setEnabled(int $id, bool $enabled): void
    {
        $this->snapshot->withLock(self::LOCK_NAME, function () use ($id, $enabled) {
            $package = $this->getById($id);
            $package->setBoxpackerEnabled((int)$enabled);
            $this->resource->save($package);
        });
    }
}
