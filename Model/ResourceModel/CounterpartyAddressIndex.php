<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Perspective\NovaposhtaShipping\Api\Data\CounterpartyAddressIndexInterface;

class CounterpartyAddressIndex extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                CounterpartyAddressIndexInterface::DATABASE_TABLE_NAME,
                CounterpartyAddressIndexInterface::ID
            );
    }
}
