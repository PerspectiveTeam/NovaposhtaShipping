<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\Carrier\Sender;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;

class AbstractShipment extends Template
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    protected RequestInterface $request;

    /**
     * @var \Magento\Backend\Block\Template\Context
     */
    protected Context $context;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory
     */
    protected ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory;

    protected ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel;

    protected Sender $sender;

    protected Config $config;

    protected NovaposhtaHelper $novaposhtaHelper;

    protected string $type;

    protected Mapping $carrierMapping;

    protected CityRepositoryInterface $cityRepository;

    protected CollectionFactory $counterpartyAddressIndexCollectionFactory;

    protected StoreManagerInterface $storeManager;

    protected Select2SmallFactory $select2;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Mapping $carrierMapping
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory $counterpartyAddressIndexCollectionFactory
     * @param \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory $select2SmallFactory
     * @param array $data
     * @param \Magento\Framework\Json\Helper\Data|null $jsonHelper
     * @param \Magento\Directory\Helper\Data|null $directoryHelper
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory,
        ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel,
        Sender $sender,
        Config $config,
        NovaposhtaHelper $novaposhtaHelper,
        Mapping $carrierMapping,
        CityRepositoryInterface $cityRepository,
        CollectionFactory $counterpartyAddressIndexCollectionFactory,
        Select2SmallFactory $select2SmallFactory,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->request = $context->getRequest();
        $this->orderRepository = $orderRepository;
        $this->context = $context;
        $this->storeManager = $context->getStoreManager();
        $this->checkoutOnestepPriceCacheFactory = $checkoutOnestepPriceCacheFactory;
        $this->checkoutOnestepPriceCacheResourceModel = $checkoutOnestepPriceCacheResourceModel;
        $this->sender = $sender;
        $this->config = $config;
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->carrierMapping = $carrierMapping;
        $this->cityRepository = $cityRepository;
        $this->counterpartyAddressIndexCollectionFactory = $counterpartyAddressIndexCollectionFactory;
        $this->select2 = $select2SmallFactory;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getRenderBlock()
    {
        $result = '';
        $userCache = $this->loadCachedData();
        if ($method = $userCache->getShippingMethod()) {
            $result = $method;
        }
        return $result;
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface
     */
    protected function loadCachedData(): ShippingCheckoutOnestepPriceCacheInterface
    {
        $tempModelPriceCache = $this->checkoutOnestepPriceCacheFactory->create();
        $this->checkoutOnestepPriceCacheResourceModel
            ->load(
                $tempModelPriceCache,
                $this->getQuoteId(),
                ShippingCheckoutOnestepPriceCacheInterface::CART_ID
            );
        return $tempModelPriceCache;
    }

    public function getQuoteId()
    {
        return $this->getOrder()->getQuoteId();
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        return $this->orderRepository->get((int)$this->request->getParam('order_id'));
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->config->getIsEnabledConfig();
    }

    /**
     * @param $allowedMethod
     * @param array $data
     * @return array
     */
    protected function addCurrentMethodToData(array $data): array
    {
        $data['current_method'] = $this->getType();
        return $data;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache
     * @param array $data
     * @return array
     */
    protected function appendCurrentUserAddress(ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache, array $data): array
    {
        $data['current_user_address'] = $this->carrierMapping->
        getShippingMethodClassByCode(
            $this->getType()
        )->loadAddressInfo($this->getQuoteId());
        return $data;
    }
}
