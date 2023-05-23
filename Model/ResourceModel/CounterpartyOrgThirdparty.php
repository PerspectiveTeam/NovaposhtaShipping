<?php


namespace Perspective\NovaposhtaShipping\Model\ResourceModel;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyInterface;

class CounterpartyOrgThirdparty extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this
            ->_init(
                CounterpartyOrgThirdpartyInterface::DATABASE_TABLE_NAME,
                CounterpartyOrgThirdpartyInterface::ID
            );
    }
}
