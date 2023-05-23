<?php

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Order\Shipment;

use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;

class ProduceTtnAddressAction implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Boxpacker
     */
    private $boxpacker;
    /**
     * @var array
     */
    private $optionsSeat;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterfaceFactory
     */
    private $productRepositoryInterfaceFactory;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private $cityRepository;
    /**
     * @var bool|string
     */
    private $serializedJsonString;

    /**
     * ProduceTtnAddressAction constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Perspective\NovaposhtaShipping\Helper\Boxpacker $boxpacker
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryInterfaceFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Perspective\NovaposhtaShipping\Helper\Boxpacker $boxpacker,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterfaceFactory $productRepositoryInterfaceFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
    ) {
        $this->request = $request;
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->dateTime = $dateTime;
        $this->boxpacker = $boxpacker;
        $this->orderRepository = $orderRepository;
        $this->productRepositoryInterfaceFactory = $productRepositoryInterfaceFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->cityRepository = $cityRepository;
    }

    public function produceTtnAddressAction()
    {
        $cityData = explode(',', $this->request->getParam('citySender'));
        if ($cityData[0] && $cityData[1]) {
            $citySender = $cityData[0];
            $counterpartyRef = $cityData[1];
        }
        $senderAddress = $this->request->getParam('senderAddress');
        $cityRecipient = $this->request->getParam('cityHidden');
        $streetRecipient = $this->request->getParam('street');
        $buildingRecipient = $this->request->getParam('building');
        $flatRecipient = $this->request->getParam('flat');
        $orderId = $this->request->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $recipientPhone = $order->getShippingAddress()->getTelephone();
        $contactSender = $this->request->getParam('sender');
        $counterParty = $counterpartyRef;
        $senderPhone = '0442992346';
        $Phone = $recipientPhone;

        $Firstname = $order->getShippingAddress()->getFirstname()?$order->getShippingAddress()->getFirstname():'Сергей';
        $LastName = $order->getShippingAddress()->getLastname()?$order->getShippingAddress()->getLastname():'Кущ';
        $MiddleName = $order->getShippingAddress()->getMiddlename()?$order->getShippingAddress()->getMiddlename():'Николаевич';

        $paymentMethod = $order->getPayment()->getMethod() == 'cashondelivery' ? "Cash" : "NonCash";
        $items = $order->getAllItems();
        $weight = 0;
        foreach ($items as $item) {
            $productModel = $this->productRepositoryInterfaceFactory->create()->get($item->getSku());
            $weight += ($item->getWeight() * $item->getQtyToShip());
        }
        $this->optionsSeat = $this->boxpacker->calcSeats($items);
        if ($weight < $this->novaposhtaHelper::PALLETE_THRESHOLD) {
            $cargoType = 'Cargo';
        } else {
            $cargoType = 'Pallet';
        }
        /*
         * Чел-пон вид для поиска нужного района
         */

        $cityRecipientString = $this->cityRepository->getCityByCityId($cityRecipient)->getDescriptionUa();
        $AreaAndRegionData = $this->novaposhtaHelper->getApi()->request(
            'Address',
            'searchSettlements',
            [
                'calledMethod' => 'searchSettlements',
                'CityName' => $cityRecipientString,
                'Limit' => 100,
            ]
        );
        $areaRecipient = '';
        $regionRecipient = '';
        if (array_key_exists('success', $AreaAndRegionData)) {
            foreach ($AreaAndRegionData['data'][0]['Addresses'] as $inx => $datum) {
                if ($datum['DeliveryCity'] === $this->cityRepository->getCityByCityId($cityRecipient)->getRef()) {
                    $areaRecipient = $datum['Area'];
                    $regionRecipient = $datum['Region'];
                }
            }
        }

        $recipientType = 'PrivatePerson';
        if ($order->getPayment()->getMethod() == 'cashondelivery') {
            $response = $this->novaposhtaHelper->getApi()->request('InternetDocument', 'save', [
                'NewAddress' => 1,
                'PayerType' => 'Sender',
                'PaymentMethod' => $paymentMethod,
                'CargoType' => $cargoType,
                'OptionsSeat' => $this->optionsSeat,
                'Weight' => $weight,
                'Description' => 'плитка (керамічна, гранітна, мозаїка)',
                'Cost' => round($order->getSubtotalInclTax()),
                'ServiceType' => 'DoorsDoors',
                'CitySender' => $citySender,
                'Sender' => $counterParty,
                'SenderAddress' => $senderAddress,
                'ContactSender' => $contactSender,
                'SendersPhone' => $senderPhone,
                'RecipientCityName' => $cityRecipientString,
                'BackwardDeliveryData' => [
                    'PayerType' => 'Sender',
                    'CargoType' => 'Money',
                    'RedeliveryString' => round($order->getSubtotalInclTax()),
                ],
                /*
                 * следующие поля требуют дополнительной работы с импортом городов. нужно по имени населенного пункта найти область, район и т.д.
                 */
                'RecipientArea' => $areaRecipient,
                'RecipientAreaRegions' => $regionRecipient,
                /* ^
                 * |
                 * эти поля
                 */
                'RecipientAddressName' => $streetRecipient,
                'RecipientHouse' => $buildingRecipient,
                'RecipientFlat' => $flatRecipient,
                'RecipientName' => $Firstname . ' ' . $MiddleName . ' ' . $LastName,
                'RecipientType' => $recipientType, //PrivatePerson для всех
                'RecipientsPhone' => $Phone,
                'DateTime' => $this->dateTime->date('d.m.Y'),
            ]);
        } else {
            $response = $this->novaposhtaHelper->getApi()->request('InternetDocument', 'save', [
                'NewAddress' => 1,
                'PayerType' => 'Sender',
                'PaymentMethod' => $paymentMethod,
                'CargoType' => $cargoType,
                'OptionsSeat' => $this->optionsSeat,
                'Weight' => $weight,
                'Description' => 'плитка (керамічна, гранітна, мозаїка)',
                'Cost' => round($order->getSubtotalInclTax()),
                'ServiceType' => 'DoorsDoors',
                'CitySender' => $citySender,
                'Sender' => $counterParty,
                'SenderAddress' => $senderAddress,
                'ContactSender' => $contactSender,
                'SendersPhone' => $senderPhone,
                'RecipientCityName' => $cityRecipientString,
                /*
                 * следующие поля требуют дополнительной работы с импортом городов. нужно по имени населенного пункта найти область, район и т.д.
                 */
                'RecipientArea' => $areaRecipient,
                'RecipientAreaRegions' => $regionRecipient,
                /* ^
                 * |
                 * эти поля
                 */
                'RecipientAddressName' => $streetRecipient,
                'RecipientHouse' => $buildingRecipient,
                'RecipientFlat' => $flatRecipient,
                'RecipientName' => $Firstname . ' ' . $MiddleName . ' ' . $LastName,
                'RecipientType' => $recipientType, //PrivatePerson для всех
                'RecipientsPhone' => $Phone,
                'DateTime' => $this->dateTime->date('d.m.Y'),
            ]);
        }
        $this->serializedJsonString = $this->jsonSerializer->serialize($response);
        return $this->resultJsonFactory->create()->setData($this->serializedJsonString);
    }

    public function execute()
    {
        return $this->produceTtnAddressAction();
    }
}
