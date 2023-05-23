<?php

namespace Perspective\NovaposhtaShipping\Model\Adminhtml\Quote\Info\Type;

use Magento\Quote\Api\CartRepositoryInterface;
use Perspective\NovaposhtaCatalog\Model\CityRepository;
use Perspective\NovaposhtaShipping\Api\Data\ShippingWarehouseInterface;
use Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse;
use Perspective\NovaposhtaShipping\Api\Data\ShippingWarehouseInterfaceFactory;

class WarehouseProcessor implements \Perspective\NovaposhtaShipping\Api\Data\Adminhtml\ProcessorInterface
{
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ShippingWarehouse
     */
    private $checkoutWarehouseModel;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ShippingCheckoutAddressFactory
     */
    private $checkoutWarehouseFactory;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse
     */
    private $checkoutWarehouseResourceModel;

    /**
     * @var \Perspective\NovaposhtaCatalog\Model\CityRepository
     */
    private $cityRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ShippingWarehouse
     */
    private $isDuplicateCheckoutWarehouseModel;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface
     */
    private $shippingCheckoutOnestepPriceCacheRepository;

    /**
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingWarehouseInterfaceFactory $checkoutWarehouseFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse $checkoutWarehouseResourceModel
     * @param \Perspective\NovaposhtaCatalog\Model\CityRepository $cityRepository
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface $shippingCheckoutOnestepPriceCacheRepository
     */
    public function __construct(
        ShippingWarehouseInterfaceFactory $checkoutWarehouseFactory,
        ShippingWarehouse $checkoutWarehouseResourceModel,
        CityRepository $cityRepository,
        Config $config,
        ShippingCheckoutOnestepPriceCacheRepositoryInterface $shippingCheckoutOnestepPriceCacheRepository
    ) {
        $this->checkoutWarehouseFactory = $checkoutWarehouseFactory;
        $this->checkoutWarehouseResourceModel = $checkoutWarehouseResourceModel;
        $this->cityRepository = $cityRepository;
        $this->config = $config;
        $this->shippingCheckoutOnestepPriceCacheRepository = $shippingCheckoutOnestepPriceCacheRepository;
    }

    /**
     * @inheritDoc
     */
    public function process($cart)
    {
        $this->processWarehouseCartForDuplication($cart);
        $this->processWarehouse($cart);
    }

    /**
     * @inheritDoc
     */
    public function isApplicable($cart)
    {
        $addressInformation = $cart->getShippingAddress();
        $result = false;
        if (!in_array(
            $addressInformation->getShippingMethodCode(),
            $this->config->getAllowedShippingMethodsAsArray()
        )
        ) {
            $result = false;
        }
        if (
            $addressInformation->getShippingMethodCode() === 'w2w'
            || $addressInformation->getShippingMethodCode() === 'c2w'
        ) {
            $this->shippingCheckoutOnestepPriceCacheRepository->markCartAndShippingMethod($cart->getId(), $addressInformation->getShippingMethodCode());
            $result = true;
        }
        if (!$result) {
            $this->checkoutWarehouseModel = $this->checkoutWarehouseFactory->create();
            $this->checkoutWarehouseResourceModel->load(
                $this->checkoutWarehouseModel,
                $cart->getId(),
                ShippingWarehouseInterface::CART_ID
            );
            $this->checkoutWarehouseResourceModel->delete($this->checkoutWarehouseModel);
        }
        return $result;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processWarehouse($cart): void
    {
        $addressInformation = $cart->getShippingAddress();
        if (!empty($addressInformation->getPerspectiveNovaposhtaShippingCity())) {
            $this->checkoutWarehouseModel->setCity($addressInformation->getPerspectiveNovaposhtaShippingCity());
        }
        if (!empty($addressInformation->getPerspectiveNovaposhtaShippingWarehouse())) {
            $this->checkoutWarehouseModel->setWarehouse($addressInformation->getPerspectiveNovaposhtaShippingWarehouse());
        }

        $this->checkoutWarehouseResourceModel->save($this->checkoutWarehouseModel);
    }

    /**
     * @param $cart
     */
    private function processWarehouseCartForDuplication($cart)
    {
        $this->checkoutWarehouseModel = $this->checkoutWarehouseFactory->create();
        $this->isDuplicateCheckoutWarehouseModel = $this->checkoutWarehouseFactory->create();
        $this->checkoutWarehouseResourceModel
            ->load($this->isDuplicateCheckoutWarehouseModel, $cart->getId(), ShippingWarehouseInterface::CART_ID);
        if ($this->isDuplicateCheckoutWarehouseModel->getId()) {
            $this->checkoutWarehouseModel->setId($this->isDuplicateCheckoutWarehouseModel->getId());
        }
        $this->checkoutWarehouseModel->setCartId((int)($cart->getId()) ?? null);
    }
}
