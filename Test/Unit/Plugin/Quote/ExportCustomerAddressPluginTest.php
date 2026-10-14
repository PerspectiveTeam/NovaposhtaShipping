<?php
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Plugin\Quote;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Perspective\NovaposhtaShipping\Model\CustomerAddress\DeliveryData;
use Perspective\NovaposhtaShipping\Model\ShippingAddress;
use Perspective\NovaposhtaShipping\Model\ShippingAddressFactory;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouse;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouseFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress as AddressResource;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse as WarehouseResource;
use Perspective\NovaposhtaShipping\Plugin\Quote\ExportCustomerAddressPlugin;
use PHPUnit\Framework\TestCase;

class ExportCustomerAddressPluginTest extends TestCase
{
    private function subject(string $type = 'shipping', string $method = 'novaposhtashipping_c2w'): Address
    {
        $address = $type === 'billing'
            ? $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getQuote'])->getMock()
            : (new \ReflectionClass(Address::class))->newInstanceWithoutConstructor();
        $address->setData(['address_type' => $type, 'shipping_method' => $method, 'quote_id' => 42]);
        return $address;
    }

    public function testWarehouseExportUsesTheStoredWarehouseId(): void
    {
        $warehouse = (new \ReflectionClass(ShippingWarehouse::class))->newInstanceWithoutConstructor();
        $warehouse->setData(['id' => 1, 'city_id' => 'city-ref', 'warehouse_id' => 'warehouse-ref']);
        $address = (new \ReflectionClass(ShippingAddress::class))->newInstanceWithoutConstructor();
        $warehouseFactory = $this->createStub(ShippingWarehouseFactory::class);
        $warehouseFactory->method('create')->willReturn($warehouse);
        $addressFactory = $this->createStub(ShippingAddressFactory::class);
        $addressFactory->method('create')->willReturn($address);
        $warehouseResource = $this->createMock(WarehouseResource::class);
        $warehouseResource->expects(self::once())->method('load')->with($warehouse, 42, 'cart_id')->willReturnSelf();
        $addressResource = $this->createMock(AddressResource::class);
        $addressResource->expects(self::once())->method('load')->with($address, 42, 'cart_id')->willReturnSelf();
        $service = $this->createMock(DeliveryData::class);
        $service->expects(self::once())->method('resolve')->with(self::anything(), self::callback(
            static fn(array $data): bool => $data['novaposhta_warehouse'] === 'warehouse-ref'
                && $data['city_novaposhta_field'] === 'city-ref'
        ))->willReturn(['novaposhta_warehouse' => 'warehouse-ref']);
        $result = $this->createMock(AddressInterface::class);
        $result->expects(self::once())->method('setCustomAttribute')->with(DeliveryData::ATTRIBUTE,
            '{"novaposhta_warehouse":"warehouse-ref"}')->willReturnSelf();
        $plugin = new ExportCustomerAddressPlugin($addressFactory, $addressResource, $warehouseFactory,
            $warehouseResource, $service, new Json());
        self::assertSame($result, $plugin->afterExportCustomerAddress($this->subject(), $result));
    }

    public function testStandardAndVirtualAddressesDoNotLoadOrWriteNpData(): void
    {
        $addressResource = $this->createMock(AddressResource::class);
        $addressResource->expects(self::never())->method('load');
        $warehouseResource = $this->createMock(WarehouseResource::class);
        $warehouseResource->expects(self::never())->method('load');
        $result = $this->createMock(AddressInterface::class);
        $result->expects(self::never())->method('setCustomAttribute');
        $plugin = new ExportCustomerAddressPlugin($this->createStub(ShippingAddressFactory::class), $addressResource,
            $this->createStub(ShippingWarehouseFactory::class), $warehouseResource,
            $this->createStub(DeliveryData::class), new Json());
        self::assertSame($result, $plugin->afterExportCustomerAddress($this->subject('shipping', 'flatrate_flatrate'), $result));
        $billing = $this->subject('billing');
        $quote = $this->createMock(Quote::class);
        $quote->method('isVirtual')->willReturn(true);
        $quote->expects(self::never())->method('getShippingAddress');
        $billing->expects(self::once())->method('getQuote')->willReturn($quote);
        self::assertSame($result, $plugin->afterExportCustomerAddress($billing, $result));
    }
}
