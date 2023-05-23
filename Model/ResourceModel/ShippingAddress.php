<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterface;

/**
 * Class ShippingCheckoutAddressClient
 * ShippingCheckoutAddressClient Resource model
 */
class ShippingAddress extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                ShippingAddressInterface::DATABASE_TABLE_NAME,
                ShippingAddressInterface::ID
            );
    }
}
