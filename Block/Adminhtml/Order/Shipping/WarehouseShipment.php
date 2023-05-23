<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\City;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\Warehouse;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\Carrier\Sender;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse\Collection;

class WarehouseShipment extends AbstractShipment
{

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse\Collection
     */
    private Collection $shippingCheckoutWarehouseResourceModelCollection;

    /**
     * @var false|\Magento\Framework\DataObject|\Perspective\NovaposhtaShipping\Model\ShippingWarehouse|null
     */
    private $npAddressData;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface
     */
    private WarehouseRepositoryInterface $warehouseRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory
     */
    private Select2SmallFactory $select2Factory;

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
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse\Collection $shippingCheckoutWarehouseResourceModelCollection
     * @param \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface $warehouseRepository
     * @param \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory $select2Factory
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
        Collection $shippingCheckoutWarehouseResourceModelCollection,
        WarehouseRepositoryInterface $warehouseRepository,
        Select2SmallFactory $select2Factory,
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
            $select2Factory,
            $data,
            $jsonHelper,
            $directoryHelper
        );
        $this->shippingCheckoutWarehouseResourceModelCollection = $shippingCheckoutWarehouseResourceModelCollection;
        $this->warehouseRepository = $warehouseRepository;
        $this->select2Factory = $select2Factory;
    }

    private array $deliveryDate;

    private array $deiveryPrice;


    public function getJsLayout()
    {
        $JsComponent['components']['WarehouseShippingForm']['component'] = 'Perspective_NovaposhtaShipping/js/order/shipping/delivery/warehouseDelivery';
        $JsComponent['components']['WarehouseShippingForm']['contactPersonSearchUrl'] = $this->getUrl('novaposhtashipping/order_shipment/searchContactPersonAction');
        $JsComponent['components']['WarehouseShippingForm']['contactPersonAddressSearchUrl'] = $this->getUrl('novaposhtashipping/order_shipment/searchCounterpartyAddressAction');
        $JsComponent['components']['WarehouseShippingForm']['form_key'] = $this->getFormKey();
        $JsComponent['components']['WarehouseShippingForm']['quote_id'] = $this->getQuoteId();
        $JsComponent['components']['WarehouseShippingForm']['npUrl'] = $this->getUrl('novaposhtashipping/order_shipment/produceTtnWarehouseAction');
        $JsComponent['components']['WarehouseShippingForm']['warehouseUrl'] = $this->getWarehouseControllerEndpoint();
        $JsComponent['components']['WarehouseShippingForm']['warehouseInCity'] = json_encode($this->getWarehouseList() ?? []);
        $JsComponent['components']['WarehouseShippingForm']['selectedWarehouseByUser'] = json_encode(['value' => $this->getWarehouse(), 'label' => $this->getWarehouseLabel()]);
        $this->jsLayout = $JsComponent;
        return parent::getJsLayout();
    }

    public function getCityAutocompleteHtml()
    {
        /** @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small $element */
        $element = $this->select2Factory->create();
        $element->setData('name', City::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
        $dataBindArray['scope'] = '\'cityInputAutocompleteShipping\'';
        $element->addClass('cityInputAutocompleteShippingClass');
        $element->setDataBind($dataBindArray);
        return $element->toHtml();
    }
    public function getWarehouseAutocompleteHtml()
    {
        /** @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small $element */
        $element = $this->select2Factory->create();
        $element->setData('name', Warehouse::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
        $dataBindArray['scope'] = '\'warehouseInputAutocompleteShipping\'';
        $element->addClass('warehouseInputAutocompleteShippingClass');
        $element->setDataBind($dataBindArray);
        return $element->toHtml();
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
     * @return void
     */
    public function getRecalculatedPrice()
    {
        $this->npAddressData = $this->getQuoteWarehouseClient();
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
     * @return false|\Magento\Framework\DataObject|\Perspective\NovaposhtaShipping\Model\ShippingWarehouse
     */
    public function getQuoteWarehouseClient()
    {
        return $this->shippingCheckoutWarehouseResourceModelCollection
            ->getItemByColumnValue('cart_id', (int)($this->getQuoteId()))
            ? $this->shippingCheckoutWarehouseResourceModelCollection
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

    public function getWarehouseControllerEndpoint()
    {
        return $this->storeManager->getStore(1)->getBaseUrl() . 'rest/V1/novaposhtashipping/filtered_warehouses';
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
    public function getWarehouse()
    {
        if ($this->npAddressData) {
            return $this->npAddressData->getWarehouseId();
        }
    }

    /**
     * @return mixed
     */
    public function getWarehouseLabel()
    {
        if ($this->npAddressData) {
            $warehouseRef = $this->npAddressData->getWarehouseId();
            $warehouseObj = $this->warehouseRepository->getWarehouseByWarehouseRef($warehouseRef);
            return $warehouseObj->getDescriptionUa() ? $warehouseObj->getDescriptionUa() : __('City not defined');
        }
    }

    /**
     * @return mixed
     */
    public function getWarehouseList()
    {
        if ($this->npAddressData) {
            $cityId = $this->npAddressData->getCity();
            return $this->warehouseRepository->getListOfWarehousesByCityRef($cityId, 'uk_UA');
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
