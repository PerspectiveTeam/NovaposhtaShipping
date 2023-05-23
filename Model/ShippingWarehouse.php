<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Perspective\NovaposhtaShipping\Api\Data\ShippingWarehouseInterface;


class ShippingWarehouse extends AbstractExtensibleModel implements ShippingWarehouseInterface
{
    protected function _construct()
    {
        $this->_init(\Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse::class);
    }

    /**
     * @return mixed
     */
    public function getCartId()
    {
        return $this->getData(self::CART_ID);
    }

    /**
     * @return mixed
     */
    public function getWarehouseId()
    {
        return $this->getData(self::WAREHOUSE_ID);
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->getData(self::CITY_ID);
    }

    /**
     * @param $data
     * @return \Magento\Framework\Model\AbstractModel|\Perspective\NovaposhtaShipping\Model\ShippingWarehouse
     */
    public function setCartId($data)
    {
        return $this->setData(self::CART_ID, $data);
    }

    /**
     * @param $data
     * @return \Magento\Framework\Model\AbstractModel|\Perspective\NovaposhtaShipping\Model\ShippingWarehouse
     */
    public function setWarehouse($data)
    {
        return $this->setData(self::WAREHOUSE_ID, $data);
    }

    /**
     * @param $data
     * @return \Magento\Framework\Model\AbstractModel|\Perspective\NovaposhtaShipping\Model\ShippingWarehouse
     */
    public function setCity($data)
    {
        return $this->setData(self::CITY_ID, $data);
    }
}
