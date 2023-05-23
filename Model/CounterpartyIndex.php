<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyIndexInterface;

class CounterpartyIndex extends AbstractExtensibleModel implements CounterpartyIndexInterface
{
    protected function _construct()
    {
        $this->_init(\Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndex::class);
    }
    public function setContactProperty($data)
    {
        return $this->setData(self::CONTACT_PROPERTY, $data);
    }

    public function setCounterpartyRef($data)
    {
        return $this->setData(self::COUNTERPARTY_REF, $data);
    }

    public function setCityRef($data)
    {
        return $this->setData(self::CITY_REF, $data);
    }

    public function getContactProperty()
    {
        return $this->getData(self::CONTACT_PROPERTY);
    }

    public function getCounterpartyRef()
    {
        return $this->getData(self::COUNTERPARTY_REF);
    }

    public function getCityRef()
    {
        return $this->getData(self::CITY_REF);
    }
}
