<?php

namespace Perspective\NovaposhtaShipping\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Perspective\NovaposhtaCatalog\Api\AreaRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Magento\Framework\Locale\Resolver;

/**
 * Adds a list of cities and regions to jsLayout to restore the state after a reload
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    const CITY_NOVAPOSHTA_FIELD = 'city_novaposhta_field';
    const AREA_NOVAPOSHTA_FIELD = 'area_novaposhta_field';

    /**
     * @var CityRepositoryInterface
     */
    private $cityRepository;

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var AreaRepositoryInterface
     */
    private AreaRepositoryInterface $areaRepository;

    /**
     * @var Resolver
     */
    private Resolver $localeResolver;

    public function __construct(
        DataPersistorInterface  $dataPersistor,
        CityRepositoryInterface $cityRepository,
        AreaRepositoryInterface $areaRepository,
        Resolver                $localeResolver
    ) {
        $this->cityRepository = $cityRepository;
        $this->dataPersistor = $dataPersistor;
        $this->areaRepository = $areaRepository;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        $areaRef = $this->dataPersistor->get(self::AREA_NOVAPOSHTA_FIELD);

        if ($areaRef) {
            $this->populateAreaDictionary($areaRef, $jsLayout);

            if ($cityRef = $this->dataPersistor->get(self::CITY_NOVAPOSHTA_FIELD)) {
                $this->populateCityDictionary($cityRef, $jsLayout);
            }
        }

        return $jsLayout;
    }

    /**
     * @param $areaRef
     * @param $jsLayout
     * @return void
     */
    private function populateAreaDictionary($areaRef, &$jsLayout)
    {
        $area = $this->areaRepository->getAreaByAreaRef($areaRef);

        if (empty($area->getRef())) {
            return;
        }

        $lang = $this->localeResolver->getLocale();
        if (!isset($jsLayout['components']['checkoutProvider']['dictionaries']['area'])) {
            $jsLayout['components']['checkoutProvider']['dictionaries']['area'] = [
                ['value' => $area->getRef(), 'label' => $lang === 'ru_RU' ? $area->getDescriptionRu() : $area->getDescriptionUa()],
            ];
        }
    }

    /**
     * @param $cityRef
     * @param $jsLayout
     * @return void
     */
    private function populateCityDictionary($cityRef, &$jsLayout)
    {
        $city = $this->cityRepository->getCityByCityRef($cityRef);
        if (empty($city->getRef())) {
            return;
        }

        $lang = $this->localeResolver->getLocale();
        if (!isset($jsLayout['components']['checkoutProvider']['dictionaries']['city'])) {
            $jsLayout['components']['checkoutProvider']['dictionaries']['city'] = [
                ['value' => $city->getRef(), 'label' => $lang === 'ru_RU' ? $city->getDescriptionRu() : $city->getDescriptionUa()],
            ];
        }
    }
}
