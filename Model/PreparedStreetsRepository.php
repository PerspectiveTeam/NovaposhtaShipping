<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\Data\StreetInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\PreparedStreetsRepositoryInterface;

class PreparedStreetsRepository implements PreparedStreetsRepositoryInterface
{
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private $streetRepository;

    /**
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface $streetRepository
     */
    public function __construct(
        CityRepositoryInterface $cityRepository,
        Resolver $resolver,
        SerializerInterface $serializer,
        StreetRepositoryInterface $streetRepository
    ) {
        $this->cityRepository = $cityRepository;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
        $this->streetRepository = $streetRepository;
    }

    /**
     * @inheirtDoc
     */
    public function filteredStreetsArrayByCityRefAndTerm($cityRef, $term = null)
    {
        if (!$cityRef) {
            return $this->serializer->serialize([]);
        }
        $cityObject = $this->cityRepository->getCityByCityRef($cityRef);
        $streetCollection = $this->streetRepository->getCollectionByCityRef(
            $cityObject->getRef()
        );
        $streetCollection->addFieldToFilter(
            [StreetInterface::DESCRIPTION],
            [
                ['like' => "%$term%"]
            ]
        );
        $result = [];
        /** @var \Perspective\NovaposhtaCatalog\Model\Street\Street $item */
        foreach ($streetCollection as $item) {
                $result [] = [
                    'id' => $item->getRef(),
                    'text' => $item->getDescription(),
                ];
        }
        return $this->serializer->serialize($result);
    }

    /**
     * @inheirtDoc
     */
    public function prepareStreetsArray($term = null)
    {
        if (!$term) {
            return $this->serializer->serialize([]);
        }
        $cityObject = $this->cityRepository->getCityByCityRef($term);
        $lang = $this->resolver->getLocale();
        $warehouseCollection = $this->streetRepository->getObjectByRef(
            $cityObject->getRef()
        );
        /** @var \Perspective\NovaposhtaCatalog\Model\Street\Street $item */
        foreach ($warehouseCollection as $item) {
                $result [] = [
                    'value' => $item->getRef(),
                    'label' => $item->getDescription(),
                ];

        }
        return $this->serializer->serialize($result);
    }
}
