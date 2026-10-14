<?php
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Plugin\Checkout;

use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Customer\Api\AddressRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\CustomerAddress\DeliveryData;

class CustomerAddressConfigPlugin
{
    public function __construct(
        private readonly DeliveryData $deliveryData,
        private readonly AddressRepositoryInterface $addressRepository
    )
    {
    }

    public function afterGetConfig(DefaultConfigProvider $subject, array $result): array
    {
        if (!in_array('novaposhtashipping', $result['activeCarriers'] ?? [], true)) {
            return $result;
        }
        foreach ($result['customerData']['addresses'] ?? [] as $address) {
            $attribute = $this->addressRepository->getById((int)$address['id'])->getCustomAttribute(DeliveryData::ATTRIBUTE);
            $data = $attribute ? $this->deliveryData->decode([DeliveryData::ATTRIBUTE => $attribute->getValue()]) : null;
            $result['novaposhtaCustomerAddresses'][$address['id']] = $this->deliveryData->resolve($address, $data);
        }
        return $result;
    }
}
