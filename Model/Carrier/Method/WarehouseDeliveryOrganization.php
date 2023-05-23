<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier\Method;

use Magento\Quote\Api\Data\CartInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingProcessorInterface;

class WarehouseDeliveryOrganization extends AbstractDelivery implements ShippingProcessorInterface
{
    protected const SHIPPING_CODE = ['c2w', 'w2w'];

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
    protected function isNeedToIncludePaymentInShipping(CartInterface $quote): bool
    {
        return $quote->getPayment()->getMethod() === 'cashondelivery';
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
            'ServiceType' => $object->getServiceType(),
            'Weight' => $object->getWeight(),
            'Cost' => $object->getSubtotal(),
            'CargoType' => $object->getCargoType(),
            'OptionsSeat' => $object->getOptionsSeat(),
            'VolumeGeneral' => null
        ]);
        return $price;
    }
}
