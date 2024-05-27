<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Data\Form\Element\LabelFactory;
use Magento\Framework\Data\Form\ElementFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Api\SenderRepositoryInterface;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\City;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\Street;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\Carrier\Sender;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;

class AddressShipment extends AbstractShipment
{

    /**
     * @var \Magento\Framework\DataObject|\Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    protected $npAddressData;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection
     */
    private Collection $shippingCheckoutAddressResourceModelCollection;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private StreetRepositoryInterface $streetRepository;

    /**
     * @var array|mixed|null
     */
    private $deliveryDate;

    /**
     * @var array|mixed|null
     */
    private $deiveryPrice;

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
     * @param \Magento\Framework\Data\Form\ElementFactory $elementFactory
     * @param \Perspective\NovaposhtaShipping\Api\SenderRepositoryInterface $senderRepository
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress\Collection $shippingCheckoutAddressResourceModelCollection
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
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
        ElementFactory $elementFactory,
        SenderRepositoryInterface $senderRepository,
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
            $elementFactory,
            $senderRepository,
            $data,
            $jsonHelper,
            $directoryHelper);
        $this->shippingCheckoutAddressResourceModelCollection = $shippingCheckoutAddressResourceModelCollection;
        $this->streetRepository = $streetRepository;
    }


    public function getJsLayout()
    {
        $JsComponent['components']['AddressShippingForm']['component'] = 'Perspective_NovaposhtaShipping/js/order/shipping/addressShippingFormComponent';
        $JsComponent['components']['AddressShippingForm']['form_key'] = $this->getFormKey();
        $JsComponent['components']['AddressShippingForm']['quote_id'] = $this->getQuoteId();
        $JsComponent['components']['AddressShippingForm']['npUrl'] = $this->getUrl('novaposhtashipping/order_shipment/produceTtnAddressAction');
        $this->jsLayout = $JsComponent;
//        return json_encode($this->jsLayout, JSON_UNESCAPED_SLASHES);
        return parent::getJsLayout();
    }

    public function getCityAutocompleteHtml()
    {
        /** @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small $element */
        $element = $this->elementFactory->create(Select2Small::class);
        $element->setData('name', City::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
        $dataBindArray['scope'] = '\'cityInputAutocompleteShipping\'';
        $element->addClass('cityInputAutocompleteShippingClass');
        $element->setDataBind($dataBindArray);
        return $element->toHtml();
    }

    public function getStreetAutocompleteHtml()
    {
        /** @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small $element */
        $element = $this->elementFactory->create(Select2Small::class);
        $element->setData('name', Street::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
        $dataBindArray['scope'] = '\'streetInputAutocompleteShipping\'';
        $element->addClass('streetInputAutocompleteShippingClass');
        $element->setDataBind($dataBindArray);
        return $element->toHtml();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * @return \Magento\Framework\DataObject|\Perspective\NovaposhtaShipping\Model\ShippingAddress
     */
    public function getNpAddressData()
    {
        if (empty($this->npAddressData)) {
            return new DataObject();
        }
        return $this->npAddressData;
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
     * @return mixed
     */
    public function getCityLabel()
    {
        return $this->cityRepository->getCityByCityRef($this->getNpAddressData()->getCity())->getDescriptionUa();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
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
    public function getFlatNumData()
    {
        return $this->getNpAddressData()->getFlat();
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

}
