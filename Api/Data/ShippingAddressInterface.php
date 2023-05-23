<?php


namespace Perspective\NovaposhtaShipping\Api\Data;

/**
 * Interface ShippingCheckoutAddressInterface
 */
interface ShippingAddressInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_shipping_sales_order_client_address';
    const ID = "id";
    const CART_ID = "cart_id";
    const CITY = "city";
    const AREA = "area";
    const REGION = "region";
    const STREET = "street";
    const BUILDING = "building";
    const FLAT = "flat";
    public function setCartId($data);
    public function setCity($data);
    public function setArea($data);
    public function setRegion($data);
    public function setStreet($data);
    public function setBuilding($data);
    public function setFlat($data);
    public function getCartId();
    public function getCity();
    public function getArea();
    public function getRegion();
    public function getStreet();
    public function getBuilding();
    public function getFlat();

}
