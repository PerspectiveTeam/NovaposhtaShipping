<?php


namespace Perspective\NovaposhtaShipping\Setup\Patch\Data;


use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Perspective\NovaposhtaShipping\Model\Product\Attribute\Backend\Height;
use Perspective\NovaposhtaShipping\Model\Product\Attribute\Backend\Length;
use Perspective\NovaposhtaShipping\Model\Product\Attribute\Backend\Width;

class AddAttributes implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(Product::ENTITY, 'product_width', [
            'type' => 'decimal',
            'label' => 'Width',
            'input' => 'text',
            'default' => 0,
            'backend'=> Width::class,
            'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
            'visible' => true,
            'used_in_product_listing' => true,
            'user_defined' => true,
            'required' => true,
            'group' => 'General',
            'sort_order' => 6,
            'note' => __('in mm')
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'product_length', [
            'type' => 'decimal',
            'label' => 'Length',
            'input' => 'text',
            'default' => 0,
            'backend'=> Length::class,
            'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
            'visible' => true,
            'used_in_product_listing' => true,
            'user_defined' => true,
            'required' => true,
            'group' => 'General',
            'sort_order' => 7,
            'note' => __('in mm')
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'product_height', [
            'type' => 'decimal',
            'label' => 'Height',
            'input' => 'text',
            'default' => 0,
            'backend'=> Height::class,
            'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
            'visible' => true,
            'used_in_product_listing' => true,
            'user_defined' => true,
            'required' => true,
            'group' => 'General',
            'sort_order' => 8,
            'note' => __('in mm')
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
