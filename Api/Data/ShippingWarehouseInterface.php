<?php


namespace Perspective\NovaposhtaShipping\Api\Data;

/**
 * Interface ShippingCheckoutAddressInterface
 */
interface ShippingWarehouseInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_shipping_sales_order_address';
    const ID = "id";
    const CART_ID = "cart_id";
    const WAREHOUSE_ID = "warehouse_id";
    const CITY_ID = "city_id";

    public function getCartId();

    public function getWarehouseId();

    public function getCity();

    public function setCartId($data);

    public function setWarehouse($data);

    public function setCity($data);
}
