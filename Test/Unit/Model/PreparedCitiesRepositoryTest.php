<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Model;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\Serializer\Json;
use Perspective\NovaposhtaCatalog\Api\AreaRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Model\Area\Area;
use Perspective\NovaposhtaCatalog\Model\City\City;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Area\Area\Collection as AreaCollection;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\City\City\Collection;
use Perspective\NovaposhtaShipping\Model\PreparedCitiesRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PreparedCitiesRepositoryTest extends TestCase
{
    public static function scopes(): array
    {
        return [[null, true], ['', true], ['area-ref', true], ['area-ref', false]];
    }

    #[DataProvider('scopes')]
    public function testCitySearchWorksWithoutRegionAndPreservesExplicitRegionFilter(?string $areaRef, bool $includeArea): void
    {
        $city = $this->createStub(City::class);
        $city->method('getRef')->willReturn('city-ref');
        $city->method('getDescriptionUa')->willReturn('Львів');
        $city->method('getData')->willReturnCallback(fn ($key) => $key === 'area' ? 'area-ref' : null);
        $collection = $this->createMock(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$city]));
        $collection->expects(self::once())->method('setPageSize')->with(50)->willReturnSelf();
        if ($areaRef) {
            $collection->expects(self::once())->method('addFieldToFilter')
                ->with('area', ['eq' => $areaRef])->willReturnSelf();
        } else {
            $collection->expects(self::never())->method('addFieldToFilter');
        }
        $cities = $this->createStub(CityRepositoryInterface::class);
        $cities->method('getCityCollectionByName')->willReturn($collection);
        $area = $this->createStub(Area::class);
        $area->method('getRef')->willReturn('area-ref');
        $area->method('getDescriptionUa')->willReturn('Львівська');
        $areaCollection = $this->createStub(AreaCollection::class);
        $areaCollection->method('getIterator')->willReturn(new \ArrayIterator([$area]));
        $areas = $this->createMock(AreaRepositoryInterface::class);
        $areas->expects($includeArea ? self::once() : self::never())
            ->method('getAreaCollectionByName')->willReturn($areaCollection);
        $locale = $this->createStub(Resolver::class);
        $locale->method('getLocale')->willReturn('uk_UA');
        $result = (new PreparedCitiesRepository($cities, $locale, new Json(), $areas))
            ->prepareCityArray('Львів', $areaRef, $includeArea);
        $expected = ['id' => 'city-ref', 'text' => 'Львів'];
        if ($includeArea) {
            $expected += ['areaRef' => 'area-ref', 'areaLabel' => 'Львівська'];
        }
        self::assertSame([$expected], json_decode($result, true));
    }
}
