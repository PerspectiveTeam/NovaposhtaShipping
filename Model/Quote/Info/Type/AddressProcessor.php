<?php

namespace Perspective\NovaposhtaShipping\Model\Quote\Info\Type;

use Perspective\NovaposhtaCatalog\Model\CityRepository;
use Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterfaceFactory;
use Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress;

class AddressProcessor implements \Perspective\NovaposhtaShipping\Api\Data\ProcessorInterface
{
    /**
     * @var \Perspective\NovaposhtaCatalog\Model\CityRepository
     */
    private $cityRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress
     */
    private $checkoutAddressResourceModel;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutAddressClientInterfaceFactory
     */
    private $checkoutAddressFactory;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterface
     */
    private $checkoutAddressModel;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterface
     */
    private $isDuplicateCheckoutAddressModel;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface
     */
    private $shippingCheckoutOnestepPriceCacheRepository;

    /**
     * @param \Perspective\NovaposhtaCatalog\Model\CityRepository $cityRepository
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress $checkoutAddressResourceModel
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterfaceFactory $checkoutAddressFactory
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface $shippingCheckoutOnestepPriceCacheRepository
     */
    public function __construct(
        CityRepository $cityRepository,
        ShippingAddress $checkoutAddressResourceModel,
        ShippingAddressInterfaceFactory $checkoutAddressFactory,
        Config $config,
        ShippingCheckoutOnestepPriceCacheRepositoryInterface $shippingCheckoutOnestepPriceCacheRepository
    ) {
        $this->cityRepository = $cityRepository;
        $this->checkoutAddressResourceModel = $checkoutAddressResourceModel;
        $this->checkoutAddressFactory = $checkoutAddressFactory;
        $this->config = $config;
        $this->shippingCheckoutOnestepPriceCacheRepository = $shippingCheckoutOnestepPriceCacheRepository;
    }

    /**
     * @inheritDoc
     */
    public function process($cartId, $addressInformation)
    {
        $this->processAddressCartForDuplication($cartId);
        $this->processAddress($addressInformation->getShippingAddress()->getExtensionAttributes());
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
        if ($addressInformation->getShippingMethodCode() === 'w2c'
            || $addressInformation->getShippingMethodCode() === 'c2c'
        ) {
            $this->shippingCheckoutOnestepPriceCacheRepository->markCartAndShippingMethod($cartId, $addressInformation->getShippingMethodCode());
            $result = true;
        }
        if (!$result) {
            $this->checkoutAddressModel = $this->checkoutAddressFactory->create();
            $this->checkoutAddressResourceModel->load(
                $this->checkoutAddressModel,
                $cartId,
                ShippingAddressInterface::CART_ID
            );
            $this->checkoutAddressResourceModel->delete($this->checkoutAddressModel);
        }
        return $result;
    }

    /**
     * @param $cartId
     */
    private function processAddressCartForDuplication($cartId): void
    {
        $this->checkoutAddressModel = $this->checkoutAddressFactory->create();
        $this->isDuplicateCheckoutAddressModel = $this->checkoutAddressFactory->create();
        $this->checkoutAddressResourceModel
            ->load($this->isDuplicateCheckoutAddressModel, $cartId, ShippingAddressInterface::CART_ID);
        if ($this->isDuplicateCheckoutAddressModel->getId()) {
            $this->checkoutAddressModel->setId($this->isDuplicateCheckoutAddressModel->getId());
        }
        $this->checkoutAddressModel->setCartId(isset($cartId) ? (int)($cartId) : 0);

    }

    /**
     * @param \Magento\Quote\Api\Data\AddressExtensionInterface|null $ext
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processAddress($ext): void
    {
        if (!empty($ext->getPerspectiveNovaposhtaShippingCity())) {
            $this->checkoutAddressModel->setCity($ext->getPerspectiveNovaposhtaShippingCity());
        }
        if (!empty($ext->getPerspectiveNovaposhtaShippingStreet())) {
            $this->checkoutAddressModel->setStreet($ext->getPerspectiveNovaposhtaShippingStreet());
        }
        if (!empty($ext->getPerspectiveNovaposhtaShippingBuilding())) {
            $this->checkoutAddressModel->setBuilding($ext->getPerspectiveNovaposhtaShippingBuilding());
        }
        if (!empty($ext->getPerspectiveNovaposhtaShippingFlat())) {
            $this->checkoutAddressModel->setFlat($ext->getPerspectiveNovaposhtaShippingFlat());
        }
        $this->checkoutAddressResourceModel->save($this->checkoutAddressModel);
    }
}
