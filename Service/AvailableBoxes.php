<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Service;

use DVDoug\BoxPacker\Packer;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\CollectionFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestBoxFactory;

class AvailableBoxes
{
    public function __construct(private CollectionFactory $collectionFactory, private TestBoxFactory $boxFactory)
    {
    }

    public function setAvailableBoxes(Packer $packer): void
    {
        $packages = $this->collectionFactory->create();
        $packages->addFieldToFilter('is_active', 1);
        $packages->addFieldToFilter('boxpacker_enabled', 1);
        foreach (['length', 'width', 'height'] as $dimension) {
            $packages->addFieldToFilter($dimension, ['gt' => 0]);
        }

        foreach ($packages as $package) {
            $weight = $package->getMaxWeight() ?? $package->getVolumetricWeight();
            $packer->addBox($this->boxFactory->create([
                'reference' => sprintf('%s - %s', $package->getDescriptionUa(), $package->getRef()),
                'outerWidth' => (int)ceil((float)$package->getWidth() / 10),
                'outerLength' => (int)ceil((float)$package->getLength() / 10),
                'outerDepth' => (int)ceil((float)$package->getHeight() / 10),
                'innerWidth' => (int)ceil((float)($package->getInnerWidth() ?? $package->getWidth()) / 10),
                'innerLength' => (int)ceil((float)($package->getInnerLength() ?? $package->getLength()) / 10),
                'innerDepth' => (int)ceil((float)($package->getInnerHeight() ?? $package->getHeight()) / 10),
                'emptyWeight' => (int)ceil((float)$package->getEmptyWeight() * 1000),
                'maxWeight' => (int)ceil((float)$weight * 1000),
            ]));
        }
    }
}
