<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Perspective\NovaposhtaShipping\Api\Data\ShippingWarehouseInterface;

/**
 * Class ShippingCheckoutAddress
 * ShippingCheckoutAddress Resource model
 */
class ShippingWarehouse extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                ShippingWarehouseInterface::DATABASE_TABLE_NAME,
                ShippingWarehouseInterface::ID
            );
    }
}
