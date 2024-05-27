<?php

namespace Perspective\NovaposhtaShipping\Model\Shipment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\OrderShippmentProcessorInterface;
use Perspective\NovaposhtaShipping\Helper\Boxpacker;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;

class AddressDelivery implements OrderShippmentProcessorInterface
{
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private NovaposhtaHelper $novaposhtaHelper;

    private Boxpacker $boxpacker;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private CityRepositoryInterface $cityRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache
     */
    private OperationsCache $cache;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    private DateTime $dateTime;

    /**
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Perspective\NovaposhtaShipping\Helper\Boxpacker $boxpacker
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache $cache
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        NovaposhtaHelper $novaposhtaHelper,
        Boxpacker $boxpacker,
        CityRepositoryInterface $cityRepository,
        OperationsCache $cache,
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->boxpacker = $boxpacker;
        $this->cityRepository = $cityRepository;
        $this->cache = $cache;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
    }

    /**
     * @inheritDoc
     */
    public function doInternetDocument($object)
    {
        $order = $this->orderRepository->get($object->getData('order_id'));
        $paymentMethod = $order->getPayment()->getMethod() == 'cashondelivery' ? "Cash" : "NonCash";
        list($weight, $cargoType) = $this->getCargoParams($order);
        $optionsSeat = $this->boxpacker->calcSeats($order->getAllItems());

        $sender = $object->getData('sender');
        $contactPerson = $object->getData('contact_person');
        $contactPersonAddress = $object->getData('contact_person_address');
        $storePhone = $this->scopeConfig->getValue('general/store_information/phone', ScopeInterface::SCOPE_STORE, $order->getStoreId());

        $cityRecipient = $object->getData('recipient_city');
        $streetRecipient = $object->getData('recipient_street');
        $buildingRecipient = $object->getData('recipient_building');
        $flatRecipient = $object->getData('recipient_flat');
        $recipientPhone = $order->getShippingAddress()->getTelephone();

        //В НП є перевірка на слово "ТЕСТ" в імені клієнта, якщо воно є, то відправка не відбудеться, тому використовуємо "Кастомер Покупенко Батькович"
        $Firstname = $order->getShippingAddress()->getFirstname() ?? $order->getBillingAddress()->getFirstname() ?? 'Кастомер';
        $LastName = $order->getShippingAddress()->getLastname() ?? $order->getBillingAddress()->getLastname() ?? 'Покупенко';
        $MiddleName = $order->getShippingAddress()->getMiddlename() ?? $order->getBillingAddress()->getMiddlename() ?? 'Батькович';

        /*
         * Чел-пон вид для поиска нужного района
         */
        $cityRecipientString = $this->cityRepository->getCityByCityRef($cityRecipient)->getDescriptionUa();

        $AreaAndRegionData = $this->getAreaAndRegionData($cityRecipientString);


        list($areaRecipient, $regionRecipient) = $this->getAreaAndRegion($AreaAndRegionData, $cityRecipient);
        $recipientType = 'PrivatePerson';

        if ($order->getPayment()->getMethod() == 'cashondelivery') {
            $response = $this->novaposhtaHelper->getApi()->request('InternetDocument', 'save', [
                'NewAddress' => 1,
                'PayerType' => 'Sender',
                'PaymentMethod' => $paymentMethod,
                'CargoType' => $cargoType,
                'OptionsSeat' => $optionsSeat,
                'Weight' => $weight,
                'Description' => 'плитка (керамічна, гранітна, мозаїка)',
                'Cost' => round($order->getSubtotalInclTax()),
                'ServiceType' => 'DoorsDoors',
                'CitySender' => $citySender,
                'Sender' => $sender,
                'SenderAddress' => $contactPersonAddress,
                'ContactSender' => $contactPerson,
                'SendersPhone' => $storePhone,
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
                'RecipientsPhone' => $recipientPhone,
                'DateTime' => $this->dateTime->date('d.m.Y'),
            ]);
        } else {
            $response = $this->novaposhtaHelper->getApi()->request('InternetDocument', 'save', [
                'NewAddress' => 1,
                'PayerType' => 'Sender',
                'PaymentMethod' => $paymentMethod,
                'CargoType' => $cargoType,
                'OptionsSeat' => $optionsSeat,
                'Weight' => $weight,
                'Description' => 'плитка (керамічна, гранітна, мозаїка)',
                'Cost' => round($order->getSubtotalInclTax()),
                'ServiceType' => 'DoorsDoors',
                'CitySender' => $citySender,
                'Sender' => $sender,
                'SenderAddress' => $contactPersonAddress,
                'ContactSender' => $contactPerson,
                'SendersPhone' => $storePhone,
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
                'RecipientsPhone' => $recipientPhone,
                'DateTime' => $this->dateTime->date('d.m.Y'),
            ]);
        }
        return $response;
    }

    /**
     * @param mixed $order
     * @return array
     */
    protected function getCargoParams(mixed $order): array
    {
        $items = $order->getAllItems();
        $weight = 0;
        foreach ($items as $item) {
            $weight += ($item->getWeight() * $item->getQtyToShip());
        }
        if ($weight < $this->novaposhtaHelper::PALLETE_THRESHOLD) {
            $cargoType = 'Cargo';
        } else {
            $cargoType = 'Pallet';
        }
        return array($weight, $cargoType);
    }

    /**
     * @param mixed $cityRecipientString
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getAreaAndRegionData(mixed $cityRecipientString): array
    {
        $cacheId = sprintf('searchSettlements_%s', $cityRecipientString);
        if ($cacheResult = $this->cache->load($cacheId)) {
            $response = unserialize($cacheResult);
            $AreaAndRegionData = $response;
        } else {
            $response = $this->novaposhtaHelper->getApi()->request(
                'Address',
                'searchSettlements',
                [
                    'calledMethod' => 'searchSettlements',
                    'CityName' => $cityRecipientString,
                    'Limit' => 100,
                ]
            );
            if (!empty($response)) {
                $this->cache->save(serialize($response), $cacheId);
            }
            $AreaAndRegionData = $response;
        }
        return $AreaAndRegionData;
    }

    /**
     * @param mixed $AreaAndRegionData
     * @param mixed $cityRecipient
     * @return array|string[]
     */
    protected function getAreaAndRegion(mixed $AreaAndRegionData, mixed $cityRecipient): array
    {
        $areaRecipient = '';
        $regionRecipient = '';
        if (array_key_exists('success', $AreaAndRegionData)) {
            foreach ($AreaAndRegionData['data'][0]['Addresses'] as $inx => $datum) {
                if ($datum['DeliveryCity'] === $this->cityRepository->getCityByCityRef($cityRecipient)->getRef()) {
                    $areaRecipient = $datum['Area'];
                    $regionRecipient = $datum['Region'];
                }
            }
        }
        return array($areaRecipient, $regionRecipient);
    }

    /**
     * @inheritDoc
     */
    public function isApplicable($object)
    {
        $order = $this->orderRepository->get($object->getData('order_id'));
        $carrierCode = $order->getShippingMethod()->getData('carrier_code');
        $method = $order->getShippingMethod()->getData('method');
        $currentShippingName = sprintf('%s_%s', $carrierCode, $method);
        return ($currentShippingName === 'novaposhtashipping_w2c') || ($currentShippingName === 'novaposhtashipping_c2c');
    }
}
