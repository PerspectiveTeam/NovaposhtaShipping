<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Api\Data\StoreInterface;
use Perspective\NovaposhtaCatalog\Helper\Config;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Quote\Info\Session\QuoteObject;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;
use Psr\Log\LoggerInterface;

class NovaposhtaShipping extends AbstractCarrier implements
    CarrierInterface
{

    /**
     * @var string
     */
    protected $_code = 'novaposhtashipping';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory
     */
    private $rateErrorFactory;

    /**
     * @var \Perspective\NovaposhtaCatalog\Helper\Config
     */
    private $configHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $data;

    /**
     * @var
     */
    protected $result;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected $method;

    /**
     * @var \Magento\Checkout\Api\Data\ShippingInformationInterface
     */
    private $addressInformation;

    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var string
     */
    const NP_CITY = 'perspective_novaposhta_shipping_city';

    /**
     * @var string
     */
    const NP_WAREHOUSE = 'perspective_novaposhta_shipping_warehouse';

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    private $store;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $resolver;

    /**
     * @var \Magento\Shipping\Model\Rate\Result
     */
    private $rateResult;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory
     */
    private $checkoutOnestepPriceCacheFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface
     */
    private $cart;

    /**
     * @var QuoteObject
     */
    private $session;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache
     */
    private $checkoutOnestepPriceCacheResourceModel;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\Mapping
     */
    private $carrierMapping;

    private OperationsCache $cache;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Perspective\NovaposhtaCatalog\Helper\Config $configHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Framework\Stdlib\ArrayManager $arrayManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Mapping $carrierMapping
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory
     * @param \Perspective\NovaposhtaShipping\Model\Quote\Info\Session\QuoteObject $sessionQuote
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache $cache
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        Config $configHelper,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        ArrayManager $arrayManager,
        TimezoneInterface $timezone,
        ManagerInterface $messageManager,
        StoreInterface $store,
        NovaposhtaHelper $novaposhtaHelper,
        Mapping $carrierMapping,
        Resolver $resolver,
        ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory,
        QuoteObject $sessionQuote,
        RequestInterface $request,
        OperationsCache $cache,
        ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->scopeConfig = $scopeConfig;
        $this->rateErrorFactory = $rateErrorFactory;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->data = $data;
        $this->arrayManager = $arrayManager;
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->timezone = $timezone;
        $this->messageManager = $messageManager;
        $this->store = $store;
        $this->resolver = $resolver;
        $this->checkoutOnestepPriceCacheFactory = $checkoutOnestepPriceCacheFactory;
        $this->session = $sessionQuote;
        $this->checkoutOnestepPriceCacheResourceModel = $checkoutOnestepPriceCacheResourceModel;
        $this->request = $request;
        $this->carrierMapping = $carrierMapping;
        $this->cache = $cache;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $shippingPrice = (int)($this->getConfigData('price'));
        $this->result = $this->_rateResultFactory->create();
        $data = $this->prepareData();
        $deliveryText = __('Delivery via Nova Poshta');
        $allowedMethods = $this->getNovaposhtaAllowedMethods();
        for ($i = 0; $i <= count($allowedMethods) - 1; $i++) {
            $data = $this->addCurrentMethodToData($allowedMethods[$i], $data);
            $tempModelPriceCache = $this->loadCachedData();
            $data = $this->appendCurrentUserAddress($tempModelPriceCache, $data);
            //это общее кеширование по городу. по пользователю есть отдельная модель
            $requestDataHash = spl_object_hash($request);
            $cacheId = "np_price__city_{$data['current_user_address']->getCity()}_method_{$data['current_method']}_quoteId_{$data['quote_id']}_hash_{$requestDataHash}";
            if (!empty(unserialize($this->cache->load($cacheId)))) {
                $shippingData = unserialize($this->cache->load($cacheId));
            } else {
                $shippingData = $this->novaposhtaHelper->getShippingPriceByData($data);
                $this->cache->save(serialize($shippingData), $cacheId);
                $shippingData = $this->prepareCachedData($tempModelPriceCache, $allowedMethods[$i], $shippingData);
                //на разных этапах onestep checkout от версии к версии может наблюдаться разное поведение
                if (isset($shippingData['price']) && $shippingData['price'] > 0 && $shippingData['price'] !== INF) {
                    $this->cache->save(serialize($shippingData), $cacheId);
                }
            }
            $this->method = $this->_rateMethodFactory->create();
            if (!isset($shippingData['price'])) {
                $this->makeCarrierWithError($allowedMethods[$i]);
                continue;
            }
            if (isset($shippingData['price'])) {
                if ($shippingData['price'] === INF) {
                    $this->makeCarrierWithError($allowedMethods[$i]);
                } else {
                    $this->createNormalCarrier($shippingData, $allowedMethods[$i], $deliveryText);
                }
            }
        }
        return $this->result;
    }

    /**
     * @return array
     */
    private function prepareData(): array
    {
        $data = [];
        $data = $this->prepareLocale($data);
        $data = $this->appendQuoteId($data);
        return $data;
    }

    /**
     * @param $shippingData
     * @param $allowedMethod
     * @param $deliveryText
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function createNormalCarrier($shippingData, $allowedMethod, $deliveryText)
    {
        $this->method->setCarrierTitle($this->timezone->date(strtotime($shippingData['date']))->format('d-m-Y') . ' - ' . $deliveryText);
        if (isset($shippingData)) {
            if (isset($shippingData['price'])) {
                if ($shippingData['price'] === 0) {
                    $deliveryText .= __(' Free');
                }
            }
            if (isset($shippingData['sale'])) {
                if (count($shippingData['sale']) > 0) {
                    $deliveryText .= __(' Sale product included');
                }
            }
            if (isset($shippingData['free_sample'])) {
                if (count($shippingData['free_sample']) > 0) {
                    $deliveryText .= __(' Free sample of the products included');
                }
            }
        }
        $this->method->setMethodTitle($this->getConfigData('name') . ' ' . __($allowedMethod));
        if (isset($shippingData['date'])) {
            $this->method->setMethodDescription($this->timezone->date(strtotime($shippingData['date']))->format('d-m-Y') . ' - ' . $shippingData['price'] . ' ' . __('UAH') . ' - ' . $deliveryText . ' - ');
            $this->method->setExpectedDelivery($this->timezone->date(strtotime($shippingData['date']))->format('d-m-Y'));
            $this->method->setEarliest($this->timezone->date(strtotime($shippingData['date']))->format('d-m-Y'));
        } else {
            $this->method->setMethodDescription($this->timezone->date()->format('d-m-Y') . ' - ' . ' 0 ' . __('UAH') . ' - ' . $deliveryText . ' - ');
            $this->method->setExpectedDelivery($this->timezone->date()->format('d-m-Y'));
            $this->method->setEarliest($this->timezone->date()->format('d-m-Y'));
        }

        $this->method->setCarrier($this->_code);
        $this->method->setMethod($allowedMethod);
        $this->cacheOrRetriveCachedData($shippingData, $allowedMethod);
        $this->result->append($this->method);
    }

    /**
     * @param $val
     */
    protected function makeCarrierWithError($val)
    {
        $errorNPmessage = __('Nova Poshta is unable to calculate shipping to your city. You still can to make order with your cart and our manager will contact with you');
        if (isset($val)) {
            $this->method->setCarrierTitle($this->getConfigData('name') . ' ' . __($val));
        } else {
            $this->method->setCarrierTitle($this->getConfigData('name'));
        }
        $this->method->setCarrier($this->_code);
        $this->method->setMethodTitle($errorNPmessage);
        $this->method->setMethod($val);
        $this->method->setMethodDescription($errorNPmessage);
        $this->method->setPrice(0);
        $this->method->setCost(0);
        $this->result->append($this->method);
    }

    /**
     * @param $string
     * @return bool
     */
    protected function isJson($string)
    {
        try {
            json_decode($string);
        } finally {
            return (json_last_error() == JSON_ERROR_NONE);
        }
    }

    /**
     * getAllowedMethods
     *
     * @param array
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('allowed_methods')];
    }

    /**
     * @param array $shippingData
     * @param $allowedMethod
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function cacheOrRetriveCachedData(array $shippingData, $allowedMethod)
    {
        /** @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface $priceCache */
        $tempModelPriceCache = $this->loadCachedData();
        if (isset($shippingData['price'])) {
            $this->method->setPrice($shippingData['price']);
            $this->method->setCost($shippingData['price']);
            if ($tempModelPriceCache->getShippingMethod() === $allowedMethod) {
                $tempModelPriceCache->setCartId($this->getQuoteId());
                $tempModelPriceCache->setCachePrice($shippingData['price']);
                $tempModelPriceCache->setShippingMethod($allowedMethod);
                $this->checkoutOnestepPriceCacheResourceModel->save($tempModelPriceCache);
            }
        } else {
            //для двух-шагового чекаута
            //пробуем загрузить кешированные данные
            if ($tempModelPriceCache->getId()) {
                $this->method->setPrice($tempModelPriceCache->getCachePrice());
                $this->method->setCost($tempModelPriceCache->getCachePrice());
            } else {
                $this->method->setPrice(0);
                $this->method->setCost(0);
            }
        }
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface
     */
    private function loadCachedData(): ShippingCheckoutOnestepPriceCacheInterface
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

    /**
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache
     * @param $allowedMethods
     * @param $shippingData
     * @return array
     */
    private function prepareCachedData(ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache, $allowedMethods, $shippingData): array
    {
        if ($tempModelPriceCache->getId() && $tempModelPriceCache->getShippingMethod() === $allowedMethods) {
            if (!isset($shippingData['price'])) {
                $shippingData['price'] = floatval($tempModelPriceCache->getCachePrice());
                $shippingData['deliveryMethod'] = $tempModelPriceCache->getShippingMethod();
                $shippingData['date'] = $this->timezone->date()->format('d-m-Y');
                $this->checkoutOnestepPriceCacheResourceModel
                    ->save(
                        $tempModelPriceCache,
                    );
            }
        }
        return $shippingData;
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareLocale(array $data): array
    {
        $currentStore = $this->resolver->getLocale();
        $data['locale'] = $currentStore;
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    private function appendQuoteId(array $data): array
    {
        $data['quote_id'] = $this->getQuoteId();
        return $data;
    }

    /**
     * @return int
     */
    protected function getQuoteId()
    {
        return $this->session->getQuote()->getId();
    }

    /**
     * @return false|string[]
     */
    private function getNovaposhtaAllowedMethods()
    {
        $allowedMethods = explode(',', $this->getConfigData('allowed_methods'));
        return $allowedMethods;
    }

    /**
     * @param $allowedMethod
     * @param array $data
     * @return array
     */
    private function addCurrentMethodToData($allowedMethod, array $data): array
    {
        $data['current_method'] = $allowedMethod;
        return $data;
    }

    /**
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache
     * @param array $data
     * @return array
     */
    private function appendCurrentUserAddress(ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache, array $data): array
    {
        $data['current_user_address'] = $this->carrierMapping->
        getShippingMethodClassByCode(
            $tempModelPriceCache->getShippingMethod() ?: $data['current_method']
        )->loadAddressInfo($this->getQuoteId());
        return $data;
    }
}
