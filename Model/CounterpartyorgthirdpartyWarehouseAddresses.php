<?php


namespace Perspective\NovaposhtaShipping\Model;


use Magento\Framework\Model\AbstractExtensibleModel;

class CounterpartyorgthirdpartyWarehouseAddresses extends AbstractExtensibleModel implements \Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyWarehouseInterface
{
    protected function _construct()
    {
        $this->_init(\Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyWarehouseAddress::class);
    }
    public function setContactPersonRef($data)
    {
        return $this->setData(self::CONTACTPERSONREF, $data);
    }

    public function setRef($data)
    {
        return $this->setData(self::REF, $data);
    }

    public function setCityRef($data)
    {
        return $this->setData(self::CITYREF, $data);
    }

    public function setGeneral($data)
    {
        return $this->setData(self::GENERAL, $data);
    }


    public function getContactPersonRef()
    {
        return $this->getData(self::CONTACTPERSONREF);
    }

    public function getRef()
    {
        return $this->getData(self::REF);
    }

    public function getCityRef()
    {
        return $this->getData(self::CITYREF);
    }


    public function getGeneral()
    {
        return $this->getData(self::GENERAL);
    }

    public function setCityDescription($data)
    {
        return $this->setData(self::CITYDESCRIPTION);
    }

    public function setAddressDescription($data)
    {
        return $this->setData(self::ADDRESSDESCRIPTION);
    }

    public function setWarehouseNumber($data)
    {
        return $this->setData(self::WAREHOUSENUMBER);
    }

    public function setTypeOfWarehouse($data)
    {
        return $this->setData(self::TYPEOFWAREHOUSE);
    }

    public function setTotalMaxWeightAllowed($data)
    {
        return $this->setData(self::TOTALMAXWEIGHTALLOWED);
    }

    public function setPlaceMaxWeightAllowed($data)
    {
        return $this->setData(self::PLACEMAXWEIGHTALLOWED);
    }

    public function getCityDescription()
    {
        return $this->getData(self::CITYDESCRIPTION);
    }

    public function getAddressDescription()
    {
        return $this->getData(self::ADDRESSDESCRIPTION);
    }

    public function getWarehouseNumber()
    {
        return $this->getData(self::WAREHOUSENUMBER);
    }

    public function getTypeOfWarehouse()
    {
        return $this->getData(self::WAREHOUSENUMBER);
    }

    public function getTotalMaxWeightAllowed()
    {
        return $this->getData(self::TOTALMAXWEIGHTALLOWED);
    }

    public function getPlaceMaxWeightAllowed()
    {
        return $this->getData(self::PLACEMAXWEIGHTALLOWED);
    }
}
