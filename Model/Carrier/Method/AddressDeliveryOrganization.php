<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier\Method;

use Magento\Quote\Api\Data\CartInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingProcessorInterface;

class AddressDeliveryOrganization extends AbstractDelivery implements ShippingProcessorInterface
{
    protected const SHIPPING_CODE = ['w2c', 'c2c'];

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private $streetRepository;

    public function __construct(
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository,
        \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender,
        \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi $novaposhtaApi,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Perspective\NovaposhtaShipping\Model\Carrier\AreaAndRegion $areaAndRegion,
        \Perspective\NovaposhtaShipping\Model\Carrier\ServiceType $serviceType,
        \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface $streetRepository,
        \Perspective\NovaposhtaShipping\Helper\Config $config,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $cityRepository,
            $sender,
            $novaposhtaApi,
            $timezone,
            $areaAndRegion,
            $serviceType,
            $config,
            $logger
        );
        $this->streetRepository = $streetRepository;
    }

    /**
     * @inheritDoc
     */
    public function getPrice($quote, $object)
    {
        [$quote, $object] = parent::getPrice($quote, $object);
        if ($this->isNeedToIncludePaymentInShipping($quote)) {
            // адресный расчет для наложенного платежа
            $price = $this->calculateCODShippingPrice($object);
        } else {
            // адресный расчет для безнала
            $price = $this->calculateNonCODShippingPrice($object);
        }
        return $price;
    }

    protected function isNeedToIncludePaymentInShipping(CartInterface $quote): bool
    {
        return $quote->getPayment()->getMethod() === 'cashondelivery';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable($shippingCode)
    {
        if (in_array($shippingCode, self::SHIPPING_CODE) && $this->getConfig()->getShippingConfigByCode('novaposhtashipping', 'is_organization')) {
            return true;
        }
        return false;
    }

    /**
     * @param $object
     * @return array
     */
    private function calculateCODShippingPrice($object)
    {
        $price = $this->getNovaposhtaApi()->getApi()->request('InternetDocument', 'getDocumentPrice', [
            'CitySender' => $object->getSenderCity()->getRef(),
            'CityRecipient' => $object->getCurrentUserAddress()->getCity(),
            'Sender' => $object->getDefaultSaleSenderCounterparty(),
            'PayerType' => 'Sender',
            'ContactSender' => $object->getDefaultSaleContactSenderData(),
//                    'SenderAddress' => $defaultSaleContactSenderAddressData,
            'SenderAddress' => $object->getDefaultSaleContactSenderAddressData(),

            'PaymentMethod' => $object->getPaymentMethod(),
            'Description' => __('Order') . $object->getOrderId(),
            'RecipientCityName' => $object->getCityRecipientString(),
            /*
            * следующие поля требуют дополнительной работы с импортом городов. нужно по имени населенного пункта найти область, район и т.д.
            */
            'RecipientArea' => $object->getAreaRecipient(),
            'RecipientAreaRegions' => $object->getRegionRecipient(),
            /* ^
             * |
             * эти поля
             */
            'RecipientAddressName' => $this->streetRepository->getByRef((string)$object->getCurrentUserAddress()->getStreet()),
            'RecipientHouse' => $object->getCurrentUserAddress()->getBuilding(),
            'RecipientFlat' => $object->getCurrentUserAddress()->getFlat(),
            'RecipientName' => $object->getFirstname() . ' ' . $object->getMiddleName() . ' ' . $object->getLastName(),
            'RecipientType' => $object->getRecipientType(), //PrivatePerson для всех
            'RecipientsPhone' => $object->getPhone(),
            'DateTime' => $this->getTimezone()->date()->format('d.m.Y'),// Mage::getModel('core/date')->date('d.m.Y'),
            'ServiceType' => $object->getServiceType(),
            'Weight' => $object->getWeight(),
            'Cost' => $object->getSubtotal(),
            'CargoType' => $object->getCargoType(),
            'OptionsSeat' => $object->getOptionsSeat(),
            'VolumeGeneral' => null,
            'RedeliveryCalculate' => [
                'CargoType' => 'Money',
                'Amount' => $object->getSubtotal()
            ]
        ]);
        return $price;
    }

    /**
     * @param $object
     * @return array
     */
    private function calculateNonCODShippingPrice($object)
    {
        $price = $this->getNovaposhtaApi()->getApi()->request('InternetDocument', 'getDocumentPrice', [
            'CitySender' => $object->getSenderCity()->getRef(),
            'CityRecipient' => $object->getCurrentUserAddress()->getCity(),
            'Sender' => $object->getDefaultSaleSenderCounterparty(),
            'PayerType' => 'Sender',
            'ContactSender' => $object->getDefaultSaleContactSenderData(),
//                    'SenderAddress' => $defaultSaleContactSenderAddressData,
            'SenderAddress' => $object->getDefaultSaleContactSenderAddressData(),

            'PaymentMethod' => $object->getPaymentMethod(),
            'Description' => __('Order') . $object->getOrderId(),
            'RecipientCityName' => $object->getCityRecipientString(),
            /*
            * следующие поля требуют дополнительной работы с импортом городов. нужно по имени населенного пункта найти область, район и т.д.
            */
            'RecipientArea' => $object->getAreaRecipient(),
            'RecipientAreaRegions' => $object->getRegionRecipient(),
            /* ^
             * |
             * эти поля
             */
            'RecipientAddressName' => $this->streetRepository->getByRef((string)$object->getCurrentUserAddress()->getStreet()),
            'RecipientHouse' => $object->getCurrentUserAddress()->getBuilding(),
            'RecipientFlat' => $object->getCurrentUserAddress()->getFlat(),
            'RecipientName' => $object->getFirstname() . ' ' . $object->getMiddleName() . ' ' . $object->getLastName(),
            'RecipientType' => $object->getRecipientType(), //PrivatePerson для всех
            'RecipientsPhone' => $object->getPhone(),
            'DateTime' => $this->getTimezone()->date()->format('d.m.Y'),// Mage::getModel('core/date')->date('d.m.Y'),
            'ServiceType' => $object->getServiceType(),
            'Weight' => $object->getWeight(),
            'Cost' => $object->getSubtotal(),
            'CargoType' => $object->getCargoType(),
            'OptionsSeat' => $object->getOptionsSeat(),
            'VolumeGeneral' => null,
        ]);
        return $price;
    }
}
