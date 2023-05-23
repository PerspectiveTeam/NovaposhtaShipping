<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyInterface;

class CounterpartyOrgThirdparty extends AbstractExtensibleModel implements CounterpartyOrgThirdpartyInterface
{
    protected function _construct()
    {
        $this->_init(\Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty::class);
    }

    public function setDescription($data)
    {
        return $this->setData(self::DESCRIPTION, $data);
    }

    public function setRef($data)
    {
        return $this->setData(self::REF, $data);
    }

    public function setFirstname($data)
    {
        return $this->setData(self::FIRSTNAME, $data);
    }

    public function setMiddlename($data)
    {
        return $this->setData(self::MIDDLENAME, $data);
    }

    public function setLastname($data)
    {
        return $this->setData(self::LASTNAME, $data);
    }

    public function setCounterpartyRef($data)
    {
        return $this->setData(self::COUNTERPARTY_REF, $data);
    }

    public function setPhone($data)
    {
        return $this->setData(self::PHONE, $data);
    }

    public function setInfo($data)
    {
        return $this->setData(self::INFO, $data);
    }

    public function setEmail($data)
    {
        return $this->setData(self::EMAIL, $data);
    }

    public function setContactPersonNote($data)
    {
        return $this->setData(self::CONTACT_PERSON_NOTE, $data);
    }

    public function setAddresses($data)
    {
        return $this->setData(self::ADDRESSES, json_encode($data));
    }

    public function setAdditionalPhone($data)
    {
        return $this->setData(self::ADDITIONAL_PHONE, $data);
    }

    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    public function getRef()
    {
        return $this->getData(self::REF);
    }

    public function getFirstname()
    {
        return $this->getData(self::FIRSTNAME);
    }

    public function getMiddlename()
    {
        return $this->getData(self::MIDDLENAME);
    }

    public function getLastname()
    {
        return $this->getData(self::LASTNAME);
    }

    public function getCounterpartyRef()
    {
        return $this->getData(self::COUNTERPARTY_REF);
    }

    public function getPhone()
    {
        return $this->getData(self::PHONE);
    }

    public function getInfo()
    {
        return $this->getData(self::INFO);
    }

    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    public function getContactPersonNote()
    {
        return $this->getData(self::CONTACT_PERSON_NOTE);
    }

    public function getAddresses()
    {
        return json_decode($this->getData(self::ADDRESSES), true);
    }

    public function getAdditionalPhone()
    {
        return $this->getData(self::ADDITIONAL_PHONE);
    }
}
