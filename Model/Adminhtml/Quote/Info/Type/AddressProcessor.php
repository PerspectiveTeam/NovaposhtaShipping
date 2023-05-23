<?php

namespace Perspective\NovaposhtaShipping\Model\Adminhtml\Quote\Info\Type;

use Magento\Quote\Api\CartRepositoryInterface;
use Perspective\NovaposhtaCatalog\Model\CityRepository;
use Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingAddressInterfaceFactory;
use Perspective\NovaposhtaShipping\Api\ShippingCheckoutOnestepPriceCacheRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress;

class AddressProcessor implements \Perspective\NovaposhtaShipping\Api\Data\Adminhtml\ProcessorInterface
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
    public function process($cart)
    {
        $this->processAddressCartForDuplication($cart);
        $this->processAddress($cart);
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
        if ($addressInformation->getShippingMethodCode() === 'w2c'
            || $addressInformation->getShippingMethodCode() === 'c2c'
        ) {
            $this->shippingCheckoutOnestepPriceCacheRepository->markCartAndShippingMethod($cart->getId(), $addressInformation->getShippingMethodCode());
            $result = true;
        }
        if (!$result) {
            $this->checkoutAddressModel = $this->checkoutAddressFactory->create();
            $this->checkoutAddressResourceModel->load(
                $this->checkoutAddressModel,
                $cart->getId(),
                ShippingAddressInterface::CART_ID
            );
            $this->checkoutAddressResourceModel->delete($this->checkoutAddressModel);
        }
        return $result;
    }

    /**
     * @param $cart
     */
    private function processAddressCartForDuplication($cart): void
    {
        $this->checkoutAddressModel = $this->checkoutAddressFactory->create();
        $this->isDuplicateCheckoutAddressModel = $this->checkoutAddressFactory->create();
        $this->checkoutAddressResourceModel
            ->load($this->isDuplicateCheckoutAddressModel, $cart->getId(), ShippingAddressInterface::CART_ID);
        if ($this->isDuplicateCheckoutAddressModel->getId()) {
            $this->checkoutAddressModel->setId($this->isDuplicateCheckoutAddressModel->getId());
        }
        $this->checkoutAddressModel->setCartId((int)($cart->getId()) ?? null);
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processAddress($cart): void
    {
        $addressInformation = $cart->getShippingAddress();
        if (!empty($addressInformation->getPerspectiveNovaposhtaShippingCity())) {
            $this->checkoutAddressModel->setCity($addressInformation->getPerspectiveNovaposhtaShippingCity());
        }
        if (!empty($addressInformation->getPerspectiveNovaposhtaShippingStreet())) {
            $this->checkoutAddressModel->setStreet($addressInformation->getPerspectiveNovaposhtaShippingStreet());
        }
        if (!empty($addressInformation->getPerspectiveNovaposhtaShippingBuilding())) {
            $this->checkoutAddressModel->setBuilding($addressInformation->getPerspectiveNovaposhtaShippingBuilding());
        }
        if (!empty($addressInformation->getPerspectiveNovaposhtaShippingFlat())) {
            $this->checkoutAddressModel->setFlat($addressInformation->getPerspectiveNovaposhtaShippingFlat());
        }
        $this->checkoutAddressResourceModel->save($this->checkoutAddressModel);
    }
}
