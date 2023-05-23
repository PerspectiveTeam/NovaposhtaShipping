<?php


namespace Perspective\NovaposhtaShipping\Setup\Patch\Data;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EnableNovaposhtaCache implements DataPatchInterface
{

    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    private $cacheState;

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StateInterface $cacheState
    ) {
        /**
         * If before, we pass $setup as argument in install/upgrade function, from now we start
         * inject it with DI. If you want to use setup, you can inject it, with the same way as here
         */
        $this->moduleDataSetup = $moduleDataSetup;
        $this->cacheState = $cacheState;
    }

    /**
     * @return array<mixed>
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array<mixed>
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return $this
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->cacheState->setEnabled(\Perspective\NovaposhtaShipping\Service\Cache\OperationsCache::TYPE_IDENTIFIER, true);
        $this->cacheState->persist();
        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }
}
