<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\Data\WarehouseInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\PreparedWarehousesRepositoryInterface;

class PreparedWarehousesRepository implements PreparedWarehousesRepositoryInterface
{
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface
     */
    protected $warehouseRepository;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected Resolver $resolver;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    protected CityRepositoryInterface $cityRepository;

    /**
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     */
    public function __construct(
        CityRepositoryInterface $cityRepository,
        Resolver $resolver,
        SerializerInterface $serializer,
        WarehouseRepositoryInterface $warehouseRepository
    ) {
        $this->cityRepository = $cityRepository;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * @inheirtDoc
     */
    public function filteredWarehouseArrayByCityRefAndTerm($cityRef, $term = null)
    {
        if (!$cityRef) {
            return $this->serializer->serialize([]);
        }
        $cityObject = $this->cityRepository->getCityByCityRef($cityRef);
        $lang = $this->resolver->getLocale();
        $warehouseCollection = $this->warehouseRepository->getCollectionOfWarehousesByCityRef(
            $cityObject->getRef()
        );
        $warehouseCollection->addFieldToFilter(
            [WarehouseInterface::DESCRIPTION_UA, WarehouseInterface::DESCRIPTION_RU],
            [
                ['like' => "%$term%"],
                ['like' => "%$term%"]
            ]
        );
        /** @var \Perspective\NovaposhtaCatalog\Model\Warehouse\Warehouse $item */
        foreach ($warehouseCollection as $item) {
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

    /**
     * @inheirtDoc
     */
    public function prepareWarehouseArray($term = null)
    {
        if (!$term) {
            return $this->serializer->serialize([]);
        }
        $cityObject = $this->cityRepository->getCityByCityRef($term);
        $lang = $this->resolver->getLocale();
        $warehouseCollection = $this->warehouseRepository->getArrayOfWarehouseModelsByCityRef(
            $cityObject->getRef(),
            $this->resolver->getLocale()
        );
        /** @var \Perspective\NovaposhtaCatalog\Model\Warehouse\Warehouse $item */
        foreach ($warehouseCollection as $item) {
            if ($lang === 'ru_RU') {
                $result [] = [
                    'value' => $item->getRef(),
                    'label' => $item->getDescriptionRu(),
                ];
            } elseif ($lang === 'uk_ua') {
                $result [] = [
                    'value' => $item->getRef(),
                    'label' => $item->getDescriptionUa(),
                ];
            } else {
                $result [] = [
                    'value' => $item->getRef(),
                    'label' => $item->getDescriptionUa(),
                ];
            }

        }
        return $this->serializer->serialize($result);
    }
}
