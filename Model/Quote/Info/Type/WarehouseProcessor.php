<?php

namespace Perspective\NovaposhtaShipping\Model\Quote\Info\Type;

use Perspective\NovaposhtaCatalog\Model\CityRepository;
use Perspective\NovaposhtaShipping\Api\Data\ShippingWarehouseInterface;
use Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse;
use Perspective\NovaposhtaShipping\Api\Data\ShippingWarehouseInterfaceFactory;

class WarehouseProcessor implements \Perspective\NovaposhtaShipping\Api\Data\ProcessorInterface
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
     * @param \Perspective\NovaposhtaShipping\Model\Quote\Info\Type\ShippingWarehouseFactory $checkoutWarehouseFactory
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
    public function process($cartId, $addressInformation)
    {
        $this->processWarehouseCartForDuplication($cartId);
        $this->processWarehouse($addressInformation->getShippingAddress()->getExtensionAttributes());
    }

    /**
     * @inheritDoc
     */
    public function isApplicable($cartId, $addressInformation)
    {
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
            $this->shippingCheckoutOnestepPriceCacheRepository->markCartAndShippingMethod($cartId, $addressInformation->getShippingMethodCode());
            $result = true;
        }
        if (!$result) {
            $this->checkoutWarehouseModel = $this->checkoutWarehouseFactory->create();
            $this->checkoutWarehouseResourceModel->load(
                $this->checkoutWarehouseModel,
                $cartId,
                ShippingWarehouseInterface::CART_ID
            );
            $this->checkoutWarehouseResourceModel->delete($this->checkoutWarehouseModel);
        }
        return $result;
    }

    /**
     * @param \Magento\Quote\Api\Data\AddressExtensionInterface|null $ext
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processWarehouse($ext): void
    {
        if (!empty($ext->getPerspectiveNovaposhtaShippingCity())) {
            $this->checkoutWarehouseModel->setCity($ext->getPerspectiveNovaposhtaShippingCity());
        }
        if (!empty($ext->getPerspectiveNovaposhtaShippingWarehouse())) {
            $this->checkoutWarehouseModel->setWarehouse($ext->getPerspectiveNovaposhtaShippingWarehouse());
        }

        $this->checkoutWarehouseResourceModel->save($this->checkoutWarehouseModel);
    }

    /**
     * @param $cartId
     */
    private function processWarehouseCartForDuplication($cartId)
    {
        $this->checkoutWarehouseModel = $this->checkoutWarehouseFactory->create();
        $this->isDuplicateCheckoutWarehouseModel = $this->checkoutWarehouseFactory->create();
        $this->checkoutWarehouseResourceModel
            ->load($this->isDuplicateCheckoutWarehouseModel, $cartId, ShippingWarehouseInterface::CART_ID);
        if ($this->isDuplicateCheckoutWarehouseModel->getId()) {
            $this->checkoutWarehouseModel->setId($this->isDuplicateCheckoutWarehouseModel->getId());
        }
        $this->checkoutWarehouseModel->setCartId(isset($cartId) ? (int)($cartId) : null);
    }
}
