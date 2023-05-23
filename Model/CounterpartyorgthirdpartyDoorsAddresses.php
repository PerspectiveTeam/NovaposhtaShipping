<?php


namespace Perspective\NovaposhtaShipping\Model;


use Magento\Framework\Model\AbstractExtensibleModel;

class CounterpartyorgthirdpartyDoorsAddresses extends AbstractExtensibleModel implements \Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyDoorsInterface
{
    protected function _construct()
    {
        $this->_init(\Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyDoorsAddress::class);
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

    public function setSettlementRef($data)
    {
        return $this->setData(self::SETTLEMENTREF, $data);
    }

    public function setSettlementDescription($data)
    {
        return $this->setData(self::SETTLEMENTDESCRIPTION, $data);
    }

    public function setType($data)
    {
        return $this->setData('Type', $data);
    }

    public function setRegionDescription($data)
    {
        return $this->setData(self::REGIONDESCRIPTION, $data);
    }

    public function setAreaDescription($data)
    {
        return $this->setData(self::AREADESCRIPTION, $data);
    }

    public function setStreetRef($data)
    {
        return $this->setData(self::STREETREF, $data);
    }

    public function setStreetDescription($data)
    {
        return $this->setData(self::STREETDESCRIPTION, $data);
    }

    public function setDescription($data)
    {
        return $this->setData(self::DESCRIPTION, $data);
    }

    public function setBuildingNumber($data)
    {
        return $this->setData(self::BUILDINGNUMBER, $data);
    }

    public function setFlat($data)
    {
        return $this->setData(self::FLAT, $data);
    }

    public function setFloor($data)
    {
        return $this->setData(self::FLOOR, $data);
    }

    public function setNote($data)
    {
        return $this->setData(self::NOTE, $data);
    }

    public function setAddressName($data)
    {
        return $this->setData(self::ADDRESSNAME, $data);
    }

    public function setGeneral($data)
    {
        return $this->setData(self::GENERAL, $data);
    }

    public function setStreetsTypeRef($data)
    {
        return $this->setData(self::STREETSTYPEREF, $data);
    }

    public function setStreetsType($data)
    {
        return $this->setData(self::STREETSTYPE, $data);
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

    public function getSettlementRef()
    {
        return $this->getData(self::SETTLEMENTREF);
    }

    public function getSettlementDescription()
    {
        return $this->getData(self::SETTLEMENTDESCRIPTION);
    }

    public function getType()
    {
        return $this->getData('Type');
    }

    public function getRegionDescription()
    {
        return $this->getData(self::REGIONDESCRIPTION);
    }

    public function getAreaDescription()
    {
        return $this->getData(self::AREADESCRIPTION);
    }

    public function getStreetRef()
    {
        return $this->getData(self::STREETREF);
    }

    public function getStreetDescription()
    {
        return $this->getData(self::STREETDESCRIPTION);
    }

    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    public function getBuildingNumber()
    {
        return $this->getData(self::BUILDINGNUMBER);
    }

    public function getFlat()
    {
        return $this->getData(self::FLAT);
    }

    public function getFloor()
    {
        return $this->getData(self::FLOOR);
    }

    public function getNote()
    {
        return $this->getData(self::NOTE);
    }

    public function getAddressName()
    {
        return $this->getData(self::ADDRESSNAME);
    }

    public function getGeneral()
    {
        return $this->getData(self::GENERAL);
    }

    public function getStreetsTypeRef()
    {
        return $this->getData(self::STREETSTYPEREF);
    }

    public function getStreetsType()
    {
        return $this->getData(self::STREETSTYPE);
    }
}
