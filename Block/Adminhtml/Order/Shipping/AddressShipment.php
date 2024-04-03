<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\City;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\Street;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\Carrier\Sender;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;

class AddressShipment extends AbstractShipment
{

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection
     */
    private Collection $shippingCheckoutAddressResourceModelCollection;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private StreetRepositoryInterface $streetRepository;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private SerializerInterface $serializer;

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
        SerializerInterface $serializer,
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
        $this->serializer = $serializer;
    }

    /**
     * @var \Magento\Framework\DataObject|\Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    protected $npAddressData;

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

    public function getJsLayout()
    {
        $JsComponent['components']['AddressShippingForm']['component'] = 'Perspective_NovaposhtaShipping/js/order/shipping/delivery/addressDelivery';
        $JsComponent['components']['AddressShippingForm']['contactPersonSearchUrl'] = $this->getUrl('novaposhtashipping/order_shipment/searchContactPersonAction');
        $JsComponent['components']['AddressShippingForm']['contactPersonAddressSearchUrl'] = $this->getUrl('novaposhtashipping/order_shipment/searchCounterpartyAddressAction');
        $JsComponent['components']['AddressShippingForm']['form_key'] = $this->getFormKey();
        $JsComponent['components']['AddressShippingForm']['quote_id'] = $this->getQuoteId();
        $JsComponent['components']['AddressShippingForm']['npUrl'] = $this->getUrl('novaposhtashipping/order_shipment/produceTtnAddressAction');
        $this->jsLayout = $JsComponent;
        return parent::getJsLayout();
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
    public function recalculatePrice()
    {
        $this->setNpAddressData($this->getQuoteAddressClient());
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
        return $this->getNpAddressData()->getCity();
    }

    /**
     * @return mixed
     */
    public function getCityLabel()
    {
        return $this->cityRepository->getCityByCityRef($this->getNpAddressData()->getCity())->getDescriptionUa();
    }

    /**
     * @return mixed
     */
    public function getStreetLabel()
    {
        return $this->streetRepository->getObjectByRef($this->getNpAddressData()->getStreet())->getDescription();
    }

    /**
     * @return mixed
     */
    public function getStreetData()
    {
        return $this->getNpAddressData()->getStreet();
    }

    /**
     * @return mixed
     */
    public function getBuildNumData()
    {
        return $this->getNpAddressData()->getBuilding();
    }

    /**
     * @return mixed
     */
    public function getFlat()
    {
        return $this->getNpAddressData()->getFlat();
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

    /**
     * @param $npAddressData
     * @return void
     */
    public function setNpAddressData($npAddressData)
    {
        $this->npAddressData = $npAddressData;
    }

    /**
     * @return \Magento\Framework\DataObject|\Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function getNpAddressData()
    {
        if (empty($this->npAddressData)) {
            return new DataObject();
        }
        return $this->npAddressData;
    }
}
