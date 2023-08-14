<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\City;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\Street;
use Perspective\NovaposhtaShipping\Helper\BoxpackerFactory;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\Carrier\Sender;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndex\CollectionFactory as CounterpartyIndexCollectionFactoryAlias;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory as CounterpartyOrgThirdpartyCollectionFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;

/**
 * Class Npc2cshipment
 * @package Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping
 */
class Npc2cshipment extends AbstractShipment
{


//
//    public function __construct(
//        Context $context,
//        Registry $registry,
//        Admin $adminHelper,
//        LoggerInterface $logger,
//        ProductRepositoryInterfaceFactory $productRepositoryInterfaceFactory,
//        BoxpackerFactory $boxpackerFactory,
//        NovaposhtaHelper $novaposhtaHelper,
//        Collection $shippingCheckoutAddressResourceModelCollection,
//        CityRepositoryInterface $cityRepository,
//        Config $config,
//        DateTime $dateTime,
//        OrderRepositoryInterface $orderRepository,
//        CollectionFactory $counterpartyAddressIndexCollectionFactory,
//        CounterpartyIndexCollectionFactoryAlias $counterpartyIndexCollectionFactory,
//        CounterpartyOrgThirdpartyCollectionFactory $counterpartyOrgThirdpartyCollectionFactory,
//        TimezoneInterface $timezone,
//        ProductMetadataInterface $productMetadata,
//        array $data = [],
//        ShippingHelper $shippingHelper = null,
//        TaxHelper $taxHelper = null
//    ) {
//        $this->context = $context;
//        $this->request = $context->getRequest();
//        $this->shippingCheckoutAddressResourceModelCollection = $shippingCheckoutAddressResourceModelCollection;
//        $this->logger = $logger;
//        $this->productRepositoryInterfaceFactory = $productRepositoryInterfaceFactory;
//        $this->boxpackerFactory = $boxpackerFactory;
//        $this->novaposhtaHelper = $novaposhtaHelper;
//        $this->cityRepository = $cityRepository;
//        $this->dateTime = $dateTime;
//        $this->config = $config;
//        $this->orderRepository = $orderRepository;
//        $this->counterpartyAddressIndexCollectionFactory = $counterpartyAddressIndexCollectionFactory;
//        $this->counterpartyIndexCollectionFactory = $counterpartyIndexCollectionFactory;
//        $this->counterpartyOrgThirdpartyCollectionFactory = $counterpartyOrgThirdpartyCollectionFactory;
//        $this->timezone = $timezone;
//        try {
//            $this->npAddressData = $this->getQuoteAddressClient();
//        } catch (Exception $exception) {
//            $this->logger->critical($exception->getTraceAsString());
//            $this->logger->debug($exception->getTraceAsString());
//        }
//        // если текущая версии выше 2.3 то выполняем это
//        if (version_compare($productMetadata->getVersion(), '2.3') === 1) {
//            parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);
//        } else {
//            //иначе это
//            parent::__construct(
//                $context,
//                $registry,
//                $adminHelper,
//                $data
//            );
//        }
//    }
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection
     */
    private Collection $shippingCheckoutAddressResourceModelCollection;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private StreetRepositoryInterface $streetRepository;

    /**
     * @var string
     */
    protected $code = 'c2c';

    /**
     * @var null
     */
    protected $npAddressData = null;

    /**
     * @var array|mixed|null
     */
    private $_senderCityObjArr;

    /**
     * @var array|mixed|null
     */
    private $destinationCityRef;
    /**
     * @var array|mixed|null
     */
    private $deliveryDate;
    /**
     * @var array|mixed|null
     */
    private $deiveryPrice;
    /**
     * @var array|mixed|null
     */
    private $allThirdpartyCounterparties;
    /**
     * @var array|mixed|null
     */
    private $counterpartyAddress;
    /**
     * @var array|mixed|null
     */
    private $optionsSeat;

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
     * @param \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface $streetRepository
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory $counterpartyAddressIndexCollectionFactory
     * @param \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory $select2SmallFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection $shippingCheckoutAddressResourceModelCollection
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
        StreetRepositoryInterface $streetRepository,
        CollectionFactory $counterpartyAddressIndexCollectionFactory,
        Select2SmallFactory $select2SmallFactory,
        Collection $shippingCheckoutAddressResourceModelCollection,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct(
            $context,
            $orderRepository,
            $checkoutOnestepPriceCacheFactory,
            $checkoutOnestepPriceCacheResourceModel,
            $sender,
            $config,
            $novaposhtaHelper,
            $carrierMapping,
            $cityRepository,
            $counterpartyAddressIndexCollectionFactory,
            $select2SmallFactory,
            $data,
            $jsonHelper,
            $directoryHelper);
        $this->shippingCheckoutAddressResourceModelCollection = $shippingCheckoutAddressResourceModelCollection;
        $this->streetRepository = $streetRepository;
    }

    /**
     * @return mixed
     */
    public function getCounterPartyToSend()
    {
        $allThirdpartyCounterparties = $this->counterpartyOrgThirdpartyCollectionFactory->create()
            ->getItems();
        foreach ($allThirdpartyCounterparties as $idx => $value) {
            $counterpartyCityRef = $value->getCityRef();
            $counterpartyRef = $value->getRef();
            $allThirdpartyCounterpartiesArr[$counterpartyRef] = $this->cityRepository->getCityByCityRef($counterpartyCityRef)->getDescriptionUa();
        }
        $this->allThirdpartyCounterparties = $allThirdpartyCounterparties;
        return $allThirdpartyCounterpartiesArr;
    }

    /**
     * @return array|mixed|null
     */
    public function getCounterPartyAddressToSend()
    {
        $counterpartyAddress = [];
        $parentCounterparties = $this->counterpartyIndexCollectionFactory->create()->getItems();
        /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyIndex $value */
        foreach ($parentCounterparties as $index => $value) {
            /** @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\Collection $counterparty */
            $counterparty = $this->counterpartyOrgThirdpartyCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('counterpartyRef', ['like' => $value->getCounterpartyRef()]);
            /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $valueCounterP */
            foreach ($counterparty as $indexCounterP => $valueCounterP) {
                $this->counterpartyAddress[$indexCounterP]['counterpartyRef'] = $valueCounterP->getCounterpartyRef();
                $this->counterpartyAddress[$indexCounterP]['Ref'] = $valueCounterP->getRef();
//                $counterpartyAddress[$indexCounterP]['Addresses'] = $valueCounterP->getAddresses();
                $addresses = $valueCounterP->getAddresses();
                if (isset($addresses['DoorsAddresses'])) {
                    foreach ($addresses['DoorsAddresses'] as $addressIndex => $addressValue) {
                        $this->counterpartyAddress[$indexCounterP]['Addresses'][$addressValue['Ref']] =
                            $addressValue['Type']
                            . ' ' .
                            $addressValue['SettlementDescription']
                            . ' ' .
                            $addressValue['StreetsType']
                            . ' ' .
                            $addressValue['StreetDescription']
                            . ' ' .
                            $addressValue['BuildingNumber']
                            . '. ';
                    }
                }
                $this->counterpartyAddress[$indexCounterP]['Description'] = $valueCounterP->getDescription();
            }
        }

        return $this->counterpartyAddress;
    }

    /**
     * @return mixed
     * @deprecated Не годится из-за того, что в дальнейшем понадобистся Реф контрагента. Все данные будут идти с индекса контрагентов
     * @see getCitiesDataForSelect
     */
    public function getCitiesSenderForSelect()
    {
        $senderCityArr = explode(',', $this->novaposhtaHelper->getStoreConfigByCode('novaposhtashipping', 'sender_city'));
        foreach ($senderCityArr as $idx => $value) {
            $senderCityObjArr[$idx] = $this->cityRepository->getCityByCityRef($value);
        }
        return $senderCityObjArr;
    }

    /**
     * @return mixed
     */
    public function getCitiesDataForSelect()
    {
        $counterpartyAddressIndexCollection = $this->counterpartyAddressIndexCollectionFactory->create()->getItems();
        /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex $value */
        $alrearyPushed = [];
        $senderObjArr = [];
        foreach ($counterpartyAddressIndexCollection as $idx => $value) {
            if (($value->getCityDescription() && $value->getCityRef() && $value->getCounterpartyRef()) && !in_array($value->getCityRef(), $alrearyPushed)) {
                $senderObjArr[$idx] = [
                    'CityDescription' => $value->getCityDescription(),
                    'CityRef' => $value->getCityRef(),
                    'CounterpartyRef' => $value->getCounterpartyRef(),
                ];
                $alrearyPushed[] = $value->getCityRef();
            }
        }
        return $senderObjArr;
    }

    /**
     * @return mixed|string
     */
    public function getCityAutocompleteHtml()
    {
        /** @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small $element */
        $element = $this->select2->create();
        $element->setData('name', City::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
        $dataBindArray['scope'] = '\'cityInputAutocompleteShipping\'';
        $element->addClass('cityInputAutocompleteShippingClass');
        $element->setDataBind($dataBindArray);
        return $element->toHtml();
    }

    /**
     * @return mixed|string
     */
    public function getStreetAutocompleteHtml()
    {
        /** @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small $element */
        $element = $this->select2->create();
        $element->setData('name', Street::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
        $dataBindArray['scope'] = '\'streetInputAutocompleteShipping\'';
        $element->addClass('streetInputAutocompleteShippingClass');
        $element->setDataBind($dataBindArray);
        return $element->toHtml();
    }

    /**
     * @return void
     */
    public function getRecalculatedPrice()
    {
        $this->npAddressData = $this->getQuoteAddressClient();
        $data = $this->addCurrentMethodToData(['quote_id' => $this->getQuoteId()]);
        $tempModelPriceCache = $this->loadCachedData();
        $data = $this->appendCurrentUserAddress($tempModelPriceCache, $data);
        //just to calculate all cities prices;
        $lowestShippingData = $this->novaposhtaHelper->getShippingPriceByData($data);
        $citiesPriceData = $this->novaposhtaHelper->getCityShippingPriceAndDateArr();

        foreach ($citiesPriceData as $cityPriceData) {
            $deliveryPrice = [
                'cityRef' => $cityPriceData->getCity()->getRef(),
                'data' => $cityPriceData->getPrice()['data'] ?? []
            ];
            $deliveryDate = [
                'cityRef' => $cityPriceData->getCity()->getRef(),
                'date' => $cityPriceData->getDate() ?? []
            ];
            $this->setDeliveryDate($deliveryDate);
            $this->setDeiveryPrice($deliveryPrice);
        }
    }

    /**
     * @return false|\Magento\Framework\DataObject|\Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function getQuoteAddressClient()
    {
        return $this->shippingCheckoutAddressResourceModelCollection
            ->getItemByColumnValue('cart_id', (int)($this->getQuoteId()))
            ? $this->shippingCheckoutAddressResourceModelCollection
                ->getItemByColumnValue('cart_id', (int)($this->getQuoteId()))
            : null;
    }

    /**
     * @return mixed
     */
    public function getCityData()
    {
        if ($this->npAddressData) {
            return $this->npAddressData->getCity();
        }
    }

    /**
     * @return mixed
     */
    public function getCityLabel()
    {
        if ($this->npAddressData) {
            return $this->cityRepository->getCityByCityRef($this->npAddressData->getCity())->getDescriptionUa();
        }
    }

    /**
     * @return mixed
     */
    public function getStreetLabel()
    {
        if ($this->npAddressData) {
            return $this->streetRepository->getObjectByRef($this->npAddressData->getStreet())->getDescription();
        }
    }

    /**
     * @return mixed
     */
    public function getStreetData()
    {
        if ($this->npAddressData) {
            return $this->npAddressData->getStreet();
        }
    }

    /**
     * @return mixed
     */
    public function getBuildNumData()
    {
        if ($this->npAddressData) {
            return $this->npAddressData->getBuilding();
        }
    }

    /**
     * @return mixed
     */
    public function getFlat()
    {
        if ($this->npAddressData) {
            return $this->npAddressData->getFlat();
        }
    }

    /**
     * @return mixed
     */
    public function getCitiesForFrontend()
    {
        $collectionUa = $this->cityRepository->getAllCityReturnCityId('uk_UA');
        foreach ($collectionUa as $index => $value) {
            $collection['UA_city'][] = ['label' => $value['label'], 'value' => $value['cityRef']];
        }
        return $collection;
    }

    /**
     * @param $ref
     * @return mixed
     */
    public function getCityNameByRef($ref)
    {
        return $this->cityRepository->getCityByCityRef($ref)->getDescriptionUa();
    }

    /**
     * @return array|mixed|null
     */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
     * @param array|mixed|null $deliveryDate
     */
    public function setDeliveryDate($deliveryDate)
    {
        return $this->deliveryDate[] = $deliveryDate;
    }

    /**
     * @return array|mixed|null
     */
    public function getDeiveryPrice()
    {
        return $this->deiveryPrice;
    }

    /**
     * @param array|mixed|null $deiveryPrice
     */
    public function setDeiveryPrice($deiveryPrice)
    {
        return $this->deiveryPrice[] = $deiveryPrice;
    }

    /**
     * @return array|mixed|null
     */
    public function getCounterpartyAddress()
    {
        return $this->counterpartyAddress;
    }
}
