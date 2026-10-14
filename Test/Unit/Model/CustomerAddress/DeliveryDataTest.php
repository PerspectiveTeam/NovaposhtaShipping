<?php
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Model\CustomerAddress;

use Magento\Framework\DataObject;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Perspective\NovaposhtaCatalog\Api\AreaRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaCatalog\Service\DirectorySyncMapper;
use Perspective\NovaposhtaShipping\Model\CustomerAddress\DeliveryData;
use PHPUnit\Framework\TestCase;

class DeliveryDataTest extends TestCase
{
    private function service(array $cities, array $streets = [], ?DataObject $warehouse = null): DeliveryData
    {
        $cityRepository = $this->createStub(CityRepositoryInterface::class);
        $cityRepository->method('getCityCollectionByName')->willReturn($cities);
        $areaRepository = $this->createStub(AreaRepositoryInterface::class);
        $areaRepository->method('getAreaByAreaRef')->willReturn(new DataObject(['description_ua' => 'Київська']));
        $streetRepository = $this->createStub(StreetRepositoryInterface::class);
        $streetRepository->method('getCollectionByCityRef')->willReturn(new class($streets) extends \ArrayIterator {
            public function addFieldToFilter($field, $condition): self { return $this; }
        });
        $map = $this->createStub(DirectorySyncMapper::class);
        $map->method('getRegionIdToAreaRefMap')->willReturn([1095 => 'kyiv-area']);
        $locale = $this->createStub(ResolverInterface::class);
        $locale->method('getLocale')->willReturn('uk_UA');
        $warehouseRepository = $this->createStub(WarehouseRepositoryInterface::class);
        $warehouseRepository->method('getWarehouseByWarehouseRef')->willReturn($warehouse ?? new DataObject());
        return new DeliveryData($cityRepository, $areaRepository, $streetRepository,
            $warehouseRepository, $map, $locale, new Json());
    }

    private function city(string $ref = 'kyiv'): DataObject
    {
        return new DataObject(['ref' => $ref, 'description_ua' => 'Київ', 'description_ru' => 'Киев', 'area' => 'kyiv-area']);
    }

    private function address(array $street = []): array
    {
        return ['country_id' => 'UA', 'region_id' => 1095, 'city' => 'Київ', 'street' => $street];
    }

    public function testLegacyAddressResolvesAnExactCityAndStreetWithoutInventingPrivateHouseFlag(): void
    {
        $service = $this->service([$this->city()], [new DataObject([
            'ref' => 'street-ref', 'street_type' => 'вул.', 'description' => 'Хрещатик',
        ])]);
        $result = $service->resolve($this->address(['вул. Хрещатик', '10', '1']));
        self::assertSame('kyiv', $result['city_novaposhta_field']);
        self::assertSame('street-ref', $result['novaposhta_street']);
        self::assertSame('10', $result['novaposhta_building']);
        self::assertSame('1', $result['novaposhta_flat']);
        self::assertFalse($result['novaposhta_is_private_house']);
        self::assertArrayNotHasKey('novaposhta_warehouse', $result);
    }

    public function testAmbiguousCitiesAreNotAutomaticallySelected(): void
    {
        self::assertSame([], $this->service([$this->city('first'), $this->city('second')])->resolve($this->address()));
    }

    public function testNativeRegionMustMatchTheResolvedCity(): void
    {
        $city = $this->city();
        $city->setData('area', 'another-area');
        self::assertSame([], $this->service([$city])->resolve($this->address()));
    }

    public function testAnAmbiguousStreetLeavesOnlyTheCityResolved(): void
    {
        $street = ['street_type' => 'вул.', 'description' => 'Хрещатик'];
        $result = $this->service([$this->city()], [
            new DataObject($street + ['ref' => 'first']), new DataObject($street + ['ref' => 'second']),
        ])->resolve($this->address(['вул. Хрещатик', '10', '1']));
        self::assertSame('kyiv', $result['city_novaposhta_field']);
        self::assertArrayNotHasKey('novaposhta_street', $result);
    }

    public function testForeignAndEmptyAddressesAreIgnored(): void
    {
        $service = $this->service([]);
        self::assertSame([], $service->resolve(['country_id' => 'US', 'city' => 'Kyiv']));
        self::assertSame([], $service->resolve(['country_id' => 'UA', 'city' => '']));
    }

    public function testSavedMetadataDecodesBothApiAndKeyedFormatsAndRejectsBrokenJson(): void
    {
        $service = $this->service([]);
        foreach ([[DeliveryData::ATTRIBUTE => '{"city":"Київ"}'],
            [['attribute_code' => DeliveryData::ATTRIBUTE, 'value' => '{"city":"Київ"}']]] as $attributes) {
            self::assertSame(['city' => 'Київ'], $service->decode($attributes));
        }
        self::assertSame([], $service->decode([DeliveryData::ATTRIBUTE => '{broken']));
        self::assertSame([], $service->decode([DeliveryData::ATTRIBUTE => 'null']));
    }
    public function testEditedNativeHouseAndApartmentOverrideOldStoredMetadata(): void
    {
        $service = $this->service([$this->city()], [new DataObject([
            'ref' => 'street-ref', 'street_type' => 'вул.', 'description' => 'Хрещатик',
        ])]);
        $result = $service->resolve($this->address(['вул. Хрещатик', '20', '2']), [
            'novaposhta_building' => '10', 'novaposhta_flat' => '1', 'novaposhta_is_private_house' => true,
        ]);
        self::assertSame('20', $result['novaposhta_building']);
        self::assertSame('2', $result['novaposhta_flat']);
        self::assertFalse($result['novaposhta_is_private_house']);
    }

    public function testSavedWarehouseMustBeAvailableAndBelongToTheCity(): void
    {
        foreach (['correct', 'another-city', 'inactive'] as $case) {
            $warehouse = new DataObject(['ref' => 'warehouse-ref', 'description_ua' => 'Відділення №1',
                'city_ref' => $case === 'another-city' ? 'different' : 'kyiv', 'is_active' => $case !== 'inactive']);
            $result = $this->service([$this->city()], [], $warehouse)->resolve($this->address(['Відділення №1']), [
                'novaposhta_warehouse' => 'warehouse-ref',
            ]);
            if ($case === 'correct') {
                self::assertSame('warehouse-ref', $result['novaposhta_warehouse']);
            } else {
                self::assertArrayNotHasKey('novaposhta_warehouse', $result);
            }
            self::assertArrayNotHasKey('novaposhta_street', $result);
        }
    }

}
