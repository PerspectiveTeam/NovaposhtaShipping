<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterface;

/**
 * Class ShippingAddress
 */
class ShippingAddress extends AbstractExtensibleModel implements ShippingAddressInterface
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_init(\Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress::class);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function setCartId($data)
    {
        return $this->setData(self::CART_ID, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function setCity($data)
    {
        return $this->setData(self::CITY, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function setArea($data)
    {
        return $this->setData(self::AREA, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function setRegion($data)
    {
        return $this->setData(self::REGION, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function setStreet($data)
    {
        return $this->setData(self::STREET, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function setBuilding($data)
    {
        return $this->setData(self::BUILDING, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function setFlat($data)
    {
        return $this->setData(self::FLAT, $data);
    }

    /**
     * @return mixed|null
     */
    public function getCartId()
    {
        return $this->getData(self::CART_ID);
    }

    /**
     * @return mixed|null
     */
    public function getCity()
    {
        return $this->getData(self::CITY);
    }

    /**
     * @return mixed|null
     */
    public function getArea()
    {
        return $this->getData(self::AREA);
    }

    /**
     * @return mixed|null
     */
    public function getRegion()
    {
        return $this->getData(self::REGION);
    }

    /**
     * @return mixed|null
     */
    public function getStreet()
    {
        return $this->getData(self::STREET);
    }

    /**
     * @return mixed|null
     */
    public function getBuilding()
    {
        return $this->getData(self::BUILDING);
    }

    /**
     * @return mixed|null
     */
    public function getFlat()
    {
        return $this->getData(self::FLAT);
    }
    public function getId()
    {
        return parent::getId(); // TODO: Change the autogenerated stub
    }
    public function setId($value)
    {
        return parent::setId($value); // TODO: Change the autogenerated stub
    }
}