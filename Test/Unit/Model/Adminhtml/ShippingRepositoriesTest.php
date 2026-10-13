<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Model\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Perspective\NovaposhtaCatalog\Api\Data\StreetInterface;
use Perspective\NovaposhtaCatalog\Api\Data\StreetSearchResultsInterface;
use Perspective\NovaposhtaCatalog\Api\Data\WarehouseInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\Adminhtml\ShippingAddressRepository;
use Perspective\NovaposhtaShipping\Model\Adminhtml\ShippingWarehouseRepository;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress as AddressResource;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse as WarehouseResource;
use Perspective\NovaposhtaShipping\Model\ShippingAddress;
use Perspective\NovaposhtaShipping\Model\ShippingAddressFactory;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouse;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouseFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShippingRepositoriesTest extends TestCase
{
    public static function directories(): array
    {
        return [
            'empty streets' => [false, null], 'populated streets' => [false, 'street-ref'],
            'empty warehouses' => [true, null], 'populated warehouses' => [true, 'warehouse-ref'],
        ];
    }

    #[DataProvider('directories')]
    public function testRateAddressPreservesSelectedCityWhenItsDirectoryIsEmpty(bool $warehouse, ?string $ref): void
    {
        $cityRef = 'e221d64c-391c-11dd-90d9-001a92567626';
        $modelClass = $warehouse ? ShippingWarehouse::class : ShippingAddress::class;
        $model = (new \ReflectionClass($modelClass))->newInstanceWithoutConstructor();
        $factory = $this->createStub($warehouse ? ShippingWarehouseFactory::class : ShippingAddressFactory::class);
        $factory->method('create')->willReturn($model);
        $resource = $this->createMock($warehouse ? WarehouseResource::class : AddressResource::class);
        $resource->expects(self::once())->method('load')->with($model, 104, 'cart_id')->willReturnSelf();
        $resource->expects(self::never())->method('save');
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturn(['billing_address' => ['novaposhta_city' => $cityRef]]);
        $items = [];
        if ($ref !== null) {
            $item = $this->createStub($warehouse ? WarehouseInterface::class : StreetInterface::class);
            $item->method('getRef')->willReturn($ref);
            $items = [42 => $item];
        }
        if ($warehouse) {
            $catalog = $this->createMock(WarehouseRepositoryInterface::class);
            $catalog->expects(self::once())->method('getArrayOfWarehouseModelsByCityRef')
                ->with($cityRef, 'uk_UA')->willReturn($items);
            $repository = new ShippingWarehouseRepository($factory, $resource, $request, $catalog);
        } else {
            $results = $this->createStub(StreetSearchResultsInterface::class);
            $results->method('getItems')->willReturn($items);
            $catalog = $this->createMock(StreetRepositoryInterface::class);
            $catalog->expects(self::once())->method('getByCityRef')->with($cityRef)->willReturn($results);
            $repository = new ShippingAddressRepository($factory, $resource, $request, $catalog);
        }
        $result = $repository->loadAddressInfo(104);
        self::assertSame($cityRef, $result->getCity());
        self::assertSame($ref, $warehouse ? $result->getWarehouseId() : $result->getStreet());
    }
}
