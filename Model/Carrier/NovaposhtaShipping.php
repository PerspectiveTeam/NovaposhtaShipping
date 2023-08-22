<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
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
     * @var \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory
     */
    protected $rateErrorFactory;

    /**
     * @var
     */
    protected $result;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected $method;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    protected $novaposhtaHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $resolver;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory
     */
    protected $checkoutOnestepPriceCacheFactory;

    /**
     * @var QuoteObject
     */
    protected $session;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache
     */
    protected $checkoutOnestepPriceCacheResourceModel;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\Mapping
     */
    protected $carrierMapping;

    /**
     * @var \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache
     */
    protected OperationsCache $cache;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Mapping $carrierMapping
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory
     * @param \Perspective\NovaposhtaShipping\Model\Quote\Info\Session\QuoteObject $sessionQuote
     * @param \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache $cache
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        TimezoneInterface $timezone,
        NovaposhtaHelper $novaposhtaHelper,
        Mapping $carrierMapping,
        Resolver $resolver,
        ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory,
        QuoteObject $sessionQuote,
        OperationsCache $cache,
        ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->rateErrorFactory = $rateErrorFactory;
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->timezone = $timezone;
        $this->resolver = $resolver;
        $this->checkoutOnestepPriceCacheFactory = $checkoutOnestepPriceCacheFactory;
        $this->session = $sessionQuote;
        $this->checkoutOnestepPriceCacheResourceModel = $checkoutOnestepPriceCacheResourceModel;
        $this->carrierMapping = $carrierMapping;
        $this->cache = $cache;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * {@inheritdoc}
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return false
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
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
    protected function prepareData(): array
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
    public function createNormalCarrier($shippingData, $allowedMethod, $deliveryText)
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
    public function makeCarrierWithError($val)
    {
        $errorNPmessage = __('Shipping cost by carrier');
        if (isset($val)) {
            $this->method->setCarrierTitle($this->getConfigData('name') . ' ' . __($val));
        } else {
            $this->method->setCarrierTitle($this->getConfigData('name'));
        }
        $this->method->setCarrier($this->_code);
        $this->method->setMethodTitle($errorNPmessage);
        $this->method->setMethod($val);
        $this->method->setMethodDescription($errorNPmessage);
        $this->method->setPrice($this->getConfigData('default_cost') ?? 0);
        $this->method->setCost($this->getConfigData('default_cost') ?? 0);
        $this->result->append($this->method);
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
    protected function cacheOrRetriveCachedData(array $shippingData, $allowedMethod)
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

    /**
     * @param \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache
     * @param $allowedMethods
     * @param $shippingData
     * @return array
     */
    protected function prepareCachedData(ShippingCheckoutOnestepPriceCacheInterface $tempModelPriceCache, $allowedMethods, $shippingData): array
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
    protected function prepareLocale(array $data): array
    {
        $currentStore = $this->resolver->getLocale();
        $data['locale'] = $currentStore;
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function appendQuoteId(array $data): array
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
    protected function getNovaposhtaAllowedMethods()
    {
        $allowedMethods = explode(',', $this->getConfigData('allowed_methods'));
        return $allowedMethods;
    }

    /**
     * @param $allowedMethod
     * @param array $data
     * @return array
     */
    protected function addCurrentMethodToData($allowedMethod, array $data): array
    {
        $data['current_method'] = $allowedMethod;
        return $data;
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
            $tempModelPriceCache->getShippingMethod() ?: $data['current_method']
        )->loadAddressInfo($this->getQuoteId());
        return $data;
    }
}
