<?php
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Model\CustomerAddress;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Locale\ResolverInterface;
use Perspective\NovaposhtaCatalog\Api\AreaRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaCatalog\Service\DirectorySyncMapper;

class DeliveryData
{
    public const ATTRIBUTE = 'novaposhta_shipping_data';

    public function __construct(
        private readonly CityRepositoryInterface $cities,
        private readonly AreaRepositoryInterface $areas,
        private readonly StreetRepositoryInterface $streets,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly DirectorySyncMapper $directoryMap,
        private readonly ResolverInterface $locale,
        private readonly Json $json
    ) {
    }

    /** Resolve only an exact, unique city/street match for addresses without NP refs. */
    public function resolve(array $address, ?array $data = null): array
    {
        if (($address['country_id'] ?? '') !== 'UA') {
            return [];
        }
        $data ??= $this->decode($address['custom_attributes'] ?? []);
        $regionId = (int)($address['region_id'] ?? $address['region']['region_id'] ?? 0);
        $areaRef = $this->directoryMap->getRegionIdToAreaRefMap()[$regionId] ?? '';
        $cityName = (string)($address['city'] ?? '');
        if ($cityName === '') {
            return [];
        }
        $matches = [];
        foreach ($this->cities->getCityCollectionByName($cityName) as $city) {
            if ((!$areaRef || $city->getData('area') === $areaRef)
                && ($this->same($cityName, (string)$city->getDescriptionUa())
                    || $this->same($cityName, (string)$city->getDescriptionRu()))
                && (empty($data['city_novaposhta_field']) || $city->getRef() === $data['city_novaposhta_field'])
            ) {
                $matches[] = $city;
            }
        }
        if (count($matches) !== 1) {
            return [];
        }
        $city = $matches[0];
        $area = $this->areas->getAreaByAreaRef((string)$city->getData('area'));
        $russian = $this->locale->getLocale() === 'ru_RU';
        $result = [
            'area_novaposhta_field' => $city->getData('area'),
            'area_novaposhta_field_label' => $russian ? $area->getDescriptionRu() : $area->getDescriptionUa(),
            'city_novaposhta_field' => $city->getRef(),
            'city' => $russian ? $city->getDescriptionRu() : $city->getDescriptionUa(),
        ];
        $lines = $address['street'] ?? [];
        $line = (string)($lines[0] ?? '');
        if (!empty($data['novaposhta_warehouse'])) {
            $warehouse = $this->warehouses->getWarehouseByWarehouseRef($data['novaposhta_warehouse']);
            if ($warehouse->getRef() && $warehouse->getData('city_ref') === $city->getRef()
                && ($warehouse->getData('is_active') === null || (bool)$warehouse->getData('is_active'))) {
                $result['novaposhta_warehouse'] = $warehouse->getRef();
                $result['novaposhta_warehouse_label'] = $russian
                    ? $warehouse->getDescriptionRu() : $warehouse->getDescriptionUa();
            }
        }
        $streetMatches = [];
        $names = array_unique([$line, preg_replace('/^\S+\s+/u', '', $line)]);
        $collection = $this->streets->getCollectionByCityRef((string)$city->getRef());
        $collection->addFieldToFilter('description', ['in' => $names]);
        foreach ($collection as $street) {
            $label = trim($street->getStreetType() . ' ' . $street->getDescription());
            if ($this->same($line, $label) || $this->same($line, (string)$street->getDescription())) {
                $streetMatches[] = ['value' => $street->getRef(), 'label' => $label];
            }
        }
        if (count($streetMatches) === 1) {
            $result['novaposhta_street'] = $streetMatches[0]['value'];
            $result['novaposhta_street_label'] = $streetMatches[0]['label'];
            $result['novaposhta_building'] = (string)($lines[1] ?? '');
            $result['novaposhta_flat'] = (string)($lines[2] ?? '');
            $result['novaposhta_is_private_house'] = $result['novaposhta_flat'] === ''
                && in_array($data['novaposhta_is_private_house'] ?? false, [true, 1, '1'], true);
        }
        return $result;
    }

    public function decode(array $attributes): array
    {
        foreach ($attributes as $key => $attribute) {
            if ($key !== self::ATTRIBUTE && ($attribute['attribute_code'] ?? '') !== self::ATTRIBUTE) {
                continue;
            }
            try {
                $data = $this->json->unserialize(is_array($attribute) ? ($attribute['value'] ?? '') : $attribute);
                return is_array($data) ? $data : [];
            } catch (\InvalidArgumentException) {
                return [];
            }
        }
        return [];
    }

    private function same(string $first, string $second): bool
    {
        return mb_strtolower(trim($first)) === mb_strtolower(trim($second));
    }
}
