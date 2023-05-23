<?php


namespace Perspective\NovaposhtaShipping\Api\Data;

/**
 * Interface ShippingCheckoutOnestepPriceCache
 */
interface ShippingCheckoutOnestepPriceCacheInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_shipping_sales_onestep_price_cache';
    const ID = "id";
    const CART_ID = "cart_id";
    const PRICE = "price";
    const METHOD = "method";
    public function getCartId();
    public function setCartId($data);
    public function getCachePrice();
    public function getShippingMethod();
    public function setCachePrice($data);
    public function setShippingMethod($data);
}
