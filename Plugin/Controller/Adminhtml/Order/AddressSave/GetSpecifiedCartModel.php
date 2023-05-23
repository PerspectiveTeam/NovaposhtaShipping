<?php

namespace Perspective\NovaposhtaShipping\Plugin\Controller\Adminhtml\Order\AddressSave;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;

class GetSpecifiedCartModel
{
    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory
     */
    private ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache
     */
    private ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    private Mapping $carrierMapping;

    public function __construct(
        ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory,
        ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel,
        OrderRepositoryInterface $orderRepository,
        Mapping $carrierMapping
    ) {
        $this->checkoutOnestepPriceCacheFactory = $checkoutOnestepPriceCacheFactory;
        $this->checkoutOnestepPriceCacheResourceModel = $checkoutOnestepPriceCacheResourceModel;
        $this->orderRepository = $orderRepository;
        $this->carrierMapping = $carrierMapping;
    }

    public function execute($entity)
    {
        try {
            if ($entity instanceof Address) {
                $quoteId = $this->orderRepository->get($entity->getParentId())->getQuoteId();
            }
            if ($entity instanceof Order) {
                $quoteId = $this->orderRepository->get($entity->getId())->getQuoteId();
            }
        } catch (NoSuchEntityException $e) {
        }
        $tempModelPriceCache = $this->loadCachedData($quoteId);
        $carrierDataModel = $this->carrierMapping->getShippingMethodClassByCode($tempModelPriceCache->getShippingMethod());
        return $carrierDataModel->loadAddressInfo($quoteId);
    }

    /**
     * @param $address
     * @return \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface
     */
    private function loadCachedData($quoteId): ShippingCheckoutOnestepPriceCacheInterface
    {
        $tempModelPriceCache = $this->checkoutOnestepPriceCacheFactory->create();

        $this->checkoutOnestepPriceCacheResourceModel
            ->load(
                $tempModelPriceCache,
                $quoteId,
                ShippingCheckoutOnestepPriceCacheInterface::CART_ID
            );
        return $tempModelPriceCache;
    }
}
