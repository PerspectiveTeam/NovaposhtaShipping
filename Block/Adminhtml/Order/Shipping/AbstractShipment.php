<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\AbstractForm;
use Magento\Framework\Data\Form\Element\Hidden;
use Magento\Framework\Data\Form\Element\Label;
use Magento\Framework\Data\Form\ElementFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Api\SenderRepositoryInterface;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2SmallFactory;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\Carrier\Sender;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;

class AbstractShipment extends Template
{
    const NOVAPOSHTA_SENDER_INPUT = 'novaposhtashipping_sender';
    const NOVAPOSHTA_SENDER_HIDDEN_INPUT = 'novaposhtashipping_sender_hidden';
    const NOVAPOSHTA_CONTACT_PERSON_INPUT = 'novaposhtashipping_contact_person';
    const NOVAPOSHTA_CONTACT_PERSON_HIDDEN_INPUT = 'novaposhtashipping_contact_person_hidden';
    const NOVAPOSHTA_CONTACT_PERSON_ADDRESS_INPUT = 'novaposhtashipping_contact_person_address';
    const NOVAPOSHTA_CONTACT_PERSON_ADDRESS_HIDDEN_INPUT = 'novaposhtashipping_contact_person_address_hidden';
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

    protected StoreManagerInterface $storeManager;

    protected SenderRepositoryInterface $senderRepository;

    protected ElementFactory $elementFactory;


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
     * @param \Magento\Framework\Data\Form\ElementFactory $elementFactory
     * @param \Perspective\NovaposhtaShipping\Api\SenderRepositoryInterface $senderRepository
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
        ElementFactory $elementFactory,
        SenderRepositoryInterface $senderRepository,
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
        $this->senderRepository = $senderRepository;
        $this->elementFactory = $elementFactory;
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

    public function getSenderHtml()
    {
        /** @var \Magento\Framework\Data\Form\Element\Label $element */
        $element = $this->elementFactory->create(Label::class);
        $element->setData('name', self::NOVAPOSHTA_SENDER_INPUT);
        ['value' => $senderValue, 'label' => $senderLabel] = $this->senderRepository->getSenderCounterparty();
        $valueToRender = sprintf("%s (%s)", $senderLabel, $senderValue);
        $element->setData('value', $valueToRender);
        return $element->toHtml();
    }

    public function getSenderHiddenHtml()
    {
        /** @var \Magento\Framework\Data\Form\Element\Label $element */
        $element = $this->elementFactory->create(Hidden::class);
        (function () {
            $form = ObjectManager::getInstance()->get(AbstractForm::class);
            $this->_form = $form;
        })->call($element);
        $element->setData('name', self::NOVAPOSHTA_SENDER_HIDDEN_INPUT);
        $element->setData('id', self::NOVAPOSHTA_SENDER_HIDDEN_INPUT);
        ['value' => $senderValue, 'label' => $senderLabel] = $this->senderRepository->getSenderCounterparty();
        $valueToRender = $senderValue;
        $element->setData('value', $valueToRender);
        return $element->toHtml();
    }

    public function getContactPersonHtml()
    {
        /** @var \Magento\Framework\Data\Form\Element\Label $element */
        $element = $this->elementFactory->create(Label::class);
        $element->setData('name', self::NOVAPOSHTA_CONTACT_PERSON_INPUT);
        ['value' => $senderValue, 'label' => $senderLabel] = $this->senderRepository->getSenderContactPerson();
        $valueToRender = sprintf("%s (%s)", $senderLabel, $senderValue);
        $element->setData('value', $valueToRender);
        return $element->toHtml();
    }

    public function getContactPersonHiddenHtml()
    {
        /** @var \Magento\Framework\Data\Form\Element\Label $element */
        $element = $this->elementFactory->create(Hidden::class);
        (function () {
            $form = ObjectManager::getInstance()->get(AbstractForm::class);
            $this->_form = $form;
        })->call($element);
        $element->setData('name', self::NOVAPOSHTA_CONTACT_PERSON_HIDDEN_INPUT);
        $element->setData('id', self::NOVAPOSHTA_CONTACT_PERSON_HIDDEN_INPUT);
        ['value' => $senderValue, 'label' => $senderLabel] = $this->senderRepository->getSenderContactPerson();
        $valueToRender = $senderValue;
        $element->setData('value', $valueToRender);
        return $element->toHtml();
    }

    public function getContactPersonAddressHtml()
    {
        /** @var \Magento\Framework\Data\Form\Element\Label $element */
        $element = $this->elementFactory->create(Label::class);
        $element->setData('name', self::NOVAPOSHTA_CONTACT_PERSON_ADDRESS_INPUT);
        ['value' => $senderValue, 'label' => $senderLabel] = $this->senderRepository->getSenderContactPersonAddress();
        $valueToRender = sprintf("%s (%s)", $senderLabel, $senderValue);
        $element->setData('value', $valueToRender);
        return $element->toHtml();
    }

    public function getContactPersonAddressHiddenHtml()
    {
        /** @var \Magento\Framework\Data\Form\Element\Label $element */
        $element = $this->elementFactory->create(Hidden::class);
        (function () {
            $form = ObjectManager::getInstance()->get(AbstractForm::class);
            $this->_form = $form;
        })->call($element);
        $element->setData('name', self::NOVAPOSHTA_CONTACT_PERSON_ADDRESS_HIDDEN_INPUT);
        $element->setData('id', self::NOVAPOSHTA_CONTACT_PERSON_ADDRESS_HIDDEN_INPUT);
        ['value' => $senderValue, 'label' => $senderLabel] = $this->senderRepository->getSenderContactPersonAddress();
        $valueToRender = $senderValue;
        $element->setData('value', $valueToRender);
        return $element->toHtml();
    }
    public function isOrganisation()
    {
        return $this->senderRepository->isOrganization();
    }

//    public function getContactPersonAddressHtml()
//    {
//        $element = $this->elementFactory->create(Select2Small::class);
//        /** @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2Small $element */
//        $element->setData('name', self::NOVAPOSHTA_CONTACT_PERSON_ADDRESS_INPUT);
//        $dataBindArray['scope'] = '\'contactPersonSenderAddressInputAutocompleteShipping\'';
//        $element->addClass('contactPersonSenderAddressInputAutocompleteShippingClass');
//        $element->setDataBind($dataBindArray);
//        return $element->toHtml();
//    }
    /**
     * @param string $path
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRestUrl(string $path)
    {
        return $this->storeManager->getStore(1)->getBaseUrl() . $path;
    }
}
