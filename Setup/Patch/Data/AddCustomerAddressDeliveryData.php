<?php
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Setup\Patch\Data;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Perspective\NovaposhtaShipping\Model\CustomerAddress\DeliveryData;

class AddCustomerAddressDeliveryData implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory
    ) {
    }

    public function apply(): self
    {
        $setup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $setup->addAttribute('customer_address', DeliveryData::ATTRIBUTE, [
            'type' => 'text',
            'label' => 'Nova Poshta delivery data',
            'input' => 'hidden',
            'required' => false,
            'visible' => false,
            'system' => false,
            'user_defined' => true,
            'group' => 'General',
        ]);
        $setup->getEavConfig()->clear();
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
