<?php

namespace Perspective\NovaposhtaShipping\Model\Shipment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Shipment\SenderCityDeterminer
     */
    private SenderCityDeterminer $senderCityDeterminer;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Perspective\NovaposhtaShipping\Helper\Boxpacker $boxpacker
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache $cache
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Perspective\NovaposhtaShipping\Model\Shipment\SenderCityDeterminer $senderCityDeterminer
     */
    public function __construct(
        NovaposhtaHelper $novaposhtaHelper,
        Boxpacker $boxpacker,
        CityRepositoryInterface $cityRepository,
        OperationsCache $cache,
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezone,
        SenderCityDeterminer $senderCityDeterminer
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->boxpacker = $boxpacker;
        $this->cityRepository = $cityRepository;
        $this->cache = $cache;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->senderCityDeterminer = $senderCityDeterminer;
        $this->timezone = $timezone;
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
        $recipientFirstname = $order->getShippingAddress()->getFirstname() ?? $order->getBillingAddress()->getFirstname() ?? 'Кастомер';
        $recipientLastName = $order->getShippingAddress()->getLastname() ?? $order->getBillingAddress()->getLastname() ?? 'Покупенко';
        $recipientMiddleName = $order->getShippingAddress()->getMiddlename() ?? $order->getBillingAddress()->getMiddlename() ?? 'Батькович';
        $recipientFullName = $this->getRecipientFullName($recipientFirstname, $recipientMiddleName, $recipientLastName);

        $internetDocumentDescription = __('Order Num: %1', $order->getIncrementId())->render();
        /*
         * Чел-пон вид для поиска нужного района
         */
        $cityRecipientString = $this->cityRepository->getCityByCityRef($cityRecipient)->getDescriptionUa();

        $AreaAndRegionData = $this->getAreaAndRegionData($cityRecipientString);

        $recipientCounterparty = $this->getRecipientCounterpartyRef($recipientFirstname, $recipientLastName);
        [
            'contact_person' => $recipientContactPerson,
            'contact_person_address' => $recipientContactPersonsAddress
        ] = $this->getRecipientContactPersonsAddress(
            $recipientCounterparty,
            $recipientFirstname,
            $recipientLastName,
            $cityRecipient,
            $streetRecipient,
            $buildingRecipient,
            $flatRecipient
        );

        list($areaRecipient, $regionRecipient) = $this->getAreaAndRegion($AreaAndRegionData, $cityRecipient);
        $recipientType = 'PrivatePerson';
        $method = $order->getShippingMethod(true)->getData('method');
        $citySender = $this->senderCityDeterminer->getCityByDeliveryTechnologyAndContactPersonAddress($method, $contactPersonAddress);
        if ($order->getPayment()->getMethod() == 'cashondelivery') {
            $response = $this->novaposhtaHelper->getApi()->request('InternetDocument', 'save', [
                'PayerType' => 'Sender',
                'PaymentMethod' => $paymentMethod,
                'CargoType' => $cargoType,
                'OptionsSeat' => $optionsSeat,
                'Weight' => $weight,
                'Description' => $internetDocumentDescription,
                'Cost' => round($order->getSubtotalInclTax()),
                'ServiceType' => 'DoorsDoors',
                'CitySender' => $citySender,
                'Sender' => $sender,
                'SenderAddress' => $contactPersonAddress,
                'ContactSender' => $contactPerson,
                'SendersPhone' => $storePhone,
                'RecipientCityName' => $cityRecipientString,
                'NewAddress' => 0,
                'Recipient'=> $recipientCounterparty,
                'RecipientAddress' => $recipientContactPersonsAddress,
                'ContactRecipient' => $recipientContactPerson,
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
                'RecipientName' => $recipientFullName,
                'RecipientType' => $recipientType, //PrivatePerson для всіх
                'RecipientsPhone' => $recipientPhone,
                'DateTime' => $this->timezone->date()->format('d.m.Y'),
            ]);
        } else {
            $response = $this->novaposhtaHelper->getApi()->request('InternetDocument', 'save', [
                'PayerType' => 'Sender',
                'PaymentMethod' => 'Cash', //todo settings in admin
                'CargoType' => $cargoType,
                'OptionsSeat' => $optionsSeat,
                'Weight' => $weight,
                'Description' => $internetDocumentDescription,
                'Cost' => round($order->getSubtotalInclTax()),
                'ServiceType' => 'DoorsDoors',
                'CitySender' => $citySender,
                'Sender' => $sender,
                'SenderAddress' => $contactPersonAddress,
                'ContactSender' => $contactPerson,
                'SendersPhone' => $storePhone,
                'NewAddress' => 0,
                'Recipient'=> $recipientCounterparty,
                'RecipientAddress' => $recipientContactPersonsAddress,
                'ContactRecipient' => $recipientContactPerson,
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
                'RecipientName' => $recipientFullName,
                'RecipientType' => $recipientType, //PrivatePerson для всех
                'RecipientsPhone' => $recipientPhone,
                'DateTime' => $this->timezone->date()->format('d.m.Y'),
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
     * Можно плагнути цей метод, бо не у всіх є по батькові в документах
     *
     * @param string $Firstname
     * @param string $MiddleName
     * @param string $LastName
     * @return string
     */
    public function getRecipientFullName(string $Firstname, string $MiddleName, string $LastName): string
    {
        $recipientFullName = sprintf('%s %s', $Firstname, $LastName);
        return $recipientFullName;
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
     * @param string $recipientFirstname
     * @param string $recipientLastName
     * @param mixed $recipientCounterparty
     * @return string
     */
    protected function getRecipientCounterpartyRef(string $recipientFirstname, string $recipientLastName): string
    {
        $recipientCounterparty = null;
        $existedRecipientCounterparties = $this->novaposhtaHelper->getApi()->getCounterparties('Recipient');
        if ($existedRecipientCounterparties && $existedRecipientCounterparties['success'] == true) {
            foreach ($existedRecipientCounterparties['data'] as $counterpartyFromApiValue) {
                if ($counterpartyFromApiValue['FirstName'] == $recipientFirstname && $counterpartyFromApiValue['LastName'] == $recipientLastName) {
                    $recipientCounterparty = $counterpartyFromApiValue['Ref'];
                }
            }
            //якщо не знайдено повної відповідності(більше потрібно для компаній, але можливий баг через "&&" в foreach)
            if (!$recipientCounterparty && count($existedRecipientCounterparties['data']) > 0) {
                $recipientCounterparty = reset($existedRecipientCounterparties['data'])['Ref'];
            }
        }
        return $recipientCounterparty;
    }

    /**
     * @param string $recipientCounterparty
     * @return array
     * @throws \Exception
     */
    protected function getRecipientContactPersonsAddress(
        $recipientCounterparty,
        $recipientFirstname,
        $recipientLastName,
        $cityRecipient,
        $streetRecipient,
        $buildingRecipient,
        $flatRecipient
    ) {
        $recipientContactPerson = null;
        $recipientContactPersonsAddress = null;
        $recipientAddressArray = $this->novaposhtaHelper->getApi()->request(
            'ContactPersonGeneral',
            'getContactPersonsList',
            [
                'CounterpartyRef' => $recipientCounterparty,
                'ContactProperty' => 'Recipient',
                'getContactPersonAddress' => 1,

            ]
        );
        if ($recipientAddressArray && $recipientAddressArray['success'] == true) {
            foreach ($recipientAddressArray['data'] as $recipientContactPersonArray) {
                if ($recipientContactPersonArray['FirstName'] == $recipientFirstname && $recipientContactPersonArray['LastName'] == $recipientLastName) {
                    $recipientContactPerson = $recipientContactPersonArray['Ref'];
                    foreach ($recipientContactPersonArray['Addresses']['DoorsAddresses'] ?? [] as $recipientAddress) {
                        if (
                            $recipientAddress['CityRef'] == $cityRecipient &&
                            $recipientAddress['StreetRef'] == $streetRecipient &&
                            $recipientAddress['BuildingNumber'] == $buildingRecipient &&
                            $recipientAddress['Flat'] == $flatRecipient
                        )
                            $recipientContactPersonsAddress = $recipientAddress['Ref'];
                    }
                }
            }
        }
        return ['contact_person' => $recipientContactPerson, 'contact_person_address' => $recipientContactPersonsAddress];
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
        $carrierCode = $order->getShippingMethod(true)->getData('carrier_code');
        $method = $order->getShippingMethod(true)->getData('method');
        $currentShippingName = sprintf('%s_%s', $carrierCode, $method);
        return ($currentShippingName === 'novaposhtashipping_w2c') || ($currentShippingName === 'novaposhtashipping_c2c');
    }
}
