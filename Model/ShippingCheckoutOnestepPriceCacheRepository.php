<?php

namespace Perspective\NovaposhtaShipping\Model;

use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache as ShippingCheckoutOnestepPriceCacheResource;

class ShippingCheckoutOnestepPriceCacheRepository implements ShippingCheckoutOnestepPriceCacheRepositoryInterface
{
    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory
     */
    private $checkoutOnestepPriceCacheFactory;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache
     */
    private $checkoutOnestepPriceCacheResourceModel;

    /**
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel
     */
    public function __construct(
        ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory,
        ShippingCheckoutOnestepPriceCacheResource $checkoutOnestepPriceCacheResourceModel
    ) {
        $this->checkoutOnestepPriceCacheFactory = $checkoutOnestepPriceCacheFactory;
        $this->checkoutOnestepPriceCacheResourceModel = $checkoutOnestepPriceCacheResourceModel;
    }

    public function markCartAndShippingMethod($cartId, $shippingMethod)
    {
        /** @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface $cacheModel */
        $cacheModel = $this->checkoutOnestepPriceCacheFactory->create();
        $this->checkoutOnestepPriceCacheResourceModel->load($cacheModel, $cartId, ShippingCheckoutOnestepPriceCacheInterface::CART_ID);
        $cacheModel->setCartId($cartId);
        $cacheModel->setShippingMethod($shippingMethod);
        $this->checkoutOnestepPriceCacheResourceModel->save($cacheModel);
    }
}
