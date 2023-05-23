<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Perspective\NovaposhtaShipping\Api\Data\CounterpartyIndexInterface;

/**
 * Class ShippingCheckoutAddress
 */
class CounterpartyIndex extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                CounterpartyIndexInterface::DATABASE_TABLE_NAME,
                CounterpartyIndexInterface::ID
            );
    }
}
