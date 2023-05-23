<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;

class PreparedCitiesRepositoryInterface implements \Perspective\NovaposhtaShipping\Api\PreparedCitiesRepositoryInterface
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
        SerializerInterface $serializer
    ) {
        $this->cityRepository = $cityRepository;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function prepareCityArray($term = null)
    {
        $items = $this->cityRepository->getCityByName($term ?? ' ');
        $lang = $this->resolver->getLocale();
        $result = [];
        /** @var \Perspective\NovaposhtaCatalog\Model\City\City $item */
        foreach ($items as $item) {
            if ($lang === 'ru_RU') {
                $result [] = [
                    'id' => $item->getRef(),
                    'text' => $item->getDescriptionRu(),
                ];
            } elseif ($lang === 'uk_ua') {
                $result [] = [
                    'id' => $item->getRef(),
                    'text' => $item->getDescriptionUa(),
                ];
            } else {
                $result [] = [
                    'id' => $item->getRef(),
                    'text' => $item->getDescriptionUa(),
                ];
            }

        }
        return $this->serializer->serialize($result);
    }
}
