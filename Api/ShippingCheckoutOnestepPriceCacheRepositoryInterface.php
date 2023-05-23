<?php

namespace Perspective\NovaposhtaShipping\Api;

interface ShippingCheckoutOnestepPriceCacheRepositoryInterface
{
    /**
     * @param $cartId
     * @param $shippingMethod
     * @return mixed
     */
    public function markCartAndShippingMethod($cartId, $shippingMethod);
}
