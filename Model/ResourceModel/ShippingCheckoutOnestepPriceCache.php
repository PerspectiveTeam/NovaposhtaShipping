<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface;

/**
 * Class ShippingCheckoutOnestepPriceCache
 * ShippingCheckoutOnestepPriceCache Resource model
 */
class ShippingCheckoutOnestepPriceCache extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                ShippingCheckoutOnestepPriceCacheInterface::DATABASE_TABLE_NAME,
                ShippingCheckoutOnestepPriceCacheInterface::ID
            );
    }
}
