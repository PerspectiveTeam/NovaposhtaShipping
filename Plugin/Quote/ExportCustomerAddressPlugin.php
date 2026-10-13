<?php
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Plugin\Quote;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Address;
use Perspective\NovaposhtaShipping\Model\CustomerAddress\DeliveryData;
use Perspective\NovaposhtaShipping\Model\ShippingAddressFactory;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouseFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress as AddressResource;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse as WarehouseResource;

class ExportCustomerAddressPlugin
{
    public function __construct(
        private readonly ShippingAddressFactory $addressFactory,
        private readonly AddressResource $addressResource,
        private readonly ShippingWarehouseFactory $warehouseFactory,
        private readonly WarehouseResource $warehouseResource,
        private readonly DeliveryData $deliveryData,
        private readonly Json $json
    ) {
    }

    public function afterExportCustomerAddress(Address $subject, AddressInterface $result): AddressInterface
    {
        $source = $subject;
        if ($subject->getAddressType() === 'billing') {
            if ($subject->getQuote()->isVirtual()) return $result;
            $shipping = $subject->getQuote()->getShippingAddress();
            if (!$shipping->getSameAsBilling() || $result->getCountryId() !== $shipping->getCountryId()
                || $result->getCity() !== $shipping->getCity()
                || $this->streetLines($result->getStreet()) !== $this->streetLines($shipping->getStreet())) {
                return $result;
            }
            $source = $shipping;
        }
        if ($source->getAddressType() !== 'shipping'
            || !str_starts_with((string)$source->getShippingMethod(), 'novaposhtashipping_')) {
            return $result;
        }
        $warehouse = $this->warehouseFactory->create();
        $this->warehouseResource->load($warehouse, $source->getQuoteId(), 'cart_id');
        $address = $this->addressFactory->create();
        $this->addressResource->load($address, $source->getQuoteId(), 'cart_id');
        $data = [
            'city_novaposhta_field' => $warehouse->getId() ? $warehouse->getCity() : $address->getCity(),
            'novaposhta_warehouse' => $warehouse->getId() ? $warehouse->getWarehouseId() : '',
            'novaposhta_building' => $address->getBuilding(),
            'novaposhta_flat' => $address->getFlat(),
            'novaposhta_is_private_house' => (bool)$address->getIsPrivateHouse(),
        ];
        $resolved = $this->deliveryData->resolve([
            'country_id' => $result->getCountryId(), 'region_id' => $result->getRegionId(),
            'city' => $result->getCity(), 'street' => $result->getStreet(),
        ], $data);
        if ($resolved) {
            $result->setCustomAttribute(DeliveryData::ATTRIBUTE, $this->json->serialize($resolved));
        }
        return $result;
    }

    private function streetLines(array $lines): array
    {
        return array_values(array_filter(array_map('trim', $lines), static fn(string $line): bool => $line !== ''));
    }
}
