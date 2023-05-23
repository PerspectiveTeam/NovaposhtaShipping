<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Perspective\NovaposhtaShipping\Api\Data\CounterpartyIndexInterface;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyDoorsInterface;

class CounterpartyOrgThirdpartyDoorsAddress extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                CounterpartyOrgThirdpartyDoorsInterface::DATABASE_TABLE_NAME,
                CounterpartyOrgThirdpartyDoorsInterface::ID
            );
    }
}
