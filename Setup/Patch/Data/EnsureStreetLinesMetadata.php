<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Setup\Patch\Data;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EnsureStreetLinesMetadata implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private Config $eavConfig
    ) {
    }

    public function apply(): self
    {
        $attribute = $this->eavConfig->getAttribute('customer_address', 'street');
        if ((int)$attribute->getData('multiline_count') < 3) {
            $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup])
                ->updateAttribute('customer_address', 'street', 'multiline_count', 3);
            $this->eavConfig->clear();
        }

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
