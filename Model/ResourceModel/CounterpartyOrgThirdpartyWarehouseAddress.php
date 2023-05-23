<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Perspective\NovaposhtaShipping\Api\Data\CounterpartyIndexInterface;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyWarehouseInterface;

class CounterpartyOrgThirdpartyWarehouseAddress extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                CounterpartyOrgThirdpartyWarehouseInterface::DATABASE_TABLE_NAME,
                CounterpartyOrgThirdpartyWarehouseInterface::ID
            );
    }
}
