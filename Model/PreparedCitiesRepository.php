<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaCatalog\Api\Data\CityInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\AreaRepositoryInterface;

class PreparedCitiesRepository implements \Perspective\NovaposhtaShipping\Api\PreparedCitiesRepositoryInterface
{
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private $cityRepository;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $resolver;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    public function __construct(
        CityRepositoryInterface $cityRepository,
        Resolver $resolver,
        SerializerInterface $serializer,
        private readonly AreaRepositoryInterface $areaRepository
    ) {
        $this->cityRepository = $cityRepository;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function prepareCityArray($term = null, $areaRef = null, $includeArea = false)
    {
        $collection = $this->cityRepository->getCityCollectionByName($term ?? '');

        if ($areaRef) {
            $collection->addFieldToFilter(CityInterface::AREA, ['eq' => $areaRef]);
        }

        $collection->setPageSize(50);

        $lang = $this->resolver->getLocale();
        $result = [];
        $areas = [];
        if ($includeArea) {
            foreach ($this->areaRepository->getAreaCollectionByName('') as $area) {
                $areas[$area->getRef()] = $lang === 'ru_RU' ? $area->getDescriptionRu() : $area->getDescriptionUa();
            }
        }
        /** @var \Perspective\NovaposhtaCatalog\Model\City\City $item */
        foreach ($collection as $item) {
            $option = [
                'id' => $item->getRef(),
                'text' => $lang === 'ru_RU' ? $item->getDescriptionRu() : $item->getDescriptionUa(),
            ];
            if ($includeArea) {
                $option['areaRef'] = $item->getData(CityInterface::AREA);
                $option['areaLabel'] = $areas[$option['areaRef']] ?? '';
            }
            $result[] = $option;
        }
        return $this->serializer->serialize($result);
    }
}
