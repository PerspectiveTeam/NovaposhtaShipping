<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Perspective\NovaposhtaCatalog\Model\Package\PackageFactory;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package as PackageResource;
use Perspective\NovaposhtaCatalog\Model\Update\DirectorySnapshot;
use Perspective\NovaposhtaShipping\Service\BoxManagement;

class AddStandardBoxes implements DataPatchInterface
{
    public function __construct(
        private PackageFactory $packageFactory,
        private PackageResource $resource,
        private DirectorySnapshot $snapshot
    ) {
    }

    public function apply()
    {
        $this->snapshot->withLock(BoxManagement::LOCK_NAME, function () {
            foreach ([
                ['80*120*170', 800, 1200, 1700, 500],
                ['120*120*170', 1200, 1200, 1700, 750],
                ['141*141*170', 1410, 1410, 1700, 1000],
            ] as [$name, $width, $length, $height, $maxWeight]) {
                $package = $this->packageFactory->create();
                $ref = 'standard-pallet-' . str_replace('*', '-', $name);
                $this->resource->load($package, $ref, 'ref');
                if ($package->getId()) {
                    continue;
                }
                $package->addData([
                    'ref' => $ref,
                    'source' => 'standard',
                    'description_ua' => $name,
                    'width' => $width,
                    'length' => $length,
                    'height' => $height,
                    'max_weight' => $maxWeight,
                    'empty_weight' => 0,
                    'is_active' => 1,
                    'boxpacker_enabled' => 1,
                ]);
                $this->resource->save($package);
            }
        });
        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
