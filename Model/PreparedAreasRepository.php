<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaCatalog\Model\Area\Area;
use Perspective\NovaposhtaCatalog\Api\AreaRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\PreparedAreasRepositoryInterface;

class PreparedAreasRepository implements PreparedAreasRepositoryInterface
{
    /**
     * @var AreaRepositoryInterface
     */
    private AreaRepositoryInterface $areaRepository;

    /**
     * @var Resolver
     */
    private Resolver $resolver;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    public function __construct(
        AreaRepositoryInterface $areaRepository,
        Resolver $resolver,
        SerializerInterface $serializer
    ) {
        $this->areaRepository = $areaRepository;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function prepareAreaArray($term = null)
    {
        $collection = $this->areaRepository->getAreaCollectionByName((string)$term);
        $collection->setPageSize(50);

        $lang = $this->resolver->getLocale();
        $result = [];
        /** @var Area $item */
        foreach ($collection as $item) {
            if ($lang === self::LOCALE_RU) {
                $result[] = [
                    'id' => $item->getRef(),
                    'text' => $item->getDescriptionRu(),
                ];
            } else {
                $result[] = [
                    'id' => $item->getRef(),
                    'text' => $item->getDescriptionUa(),
                ];
            }
        }
        return $this->serializer->serialize($result);
    }
}
