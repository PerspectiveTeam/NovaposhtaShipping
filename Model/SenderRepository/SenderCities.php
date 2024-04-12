<?php

namespace Perspective\NovaposhtaShipping\Model\SenderRepository;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\Data\CityInterface;

class SenderCities
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private CityRepositoryInterface $cityRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CityRepositoryInterface $cityRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cityRepository = $cityRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string|null $term
     * @return array
     */
    public function get($term = null)
    {
        $listOfWarehouses = $this->scopeConfig->getValue('carriers/novaposhtashipping/sender_city');
        $this->searchCriteriaBuilder->addFilter(CityInterface::CITYID, $listOfWarehouses, 'in');
        $this->searchCriteriaBuilder->addFilter(CityInterface::DESCRIPTION_UA, '%' . $term . '%', 'like');
        $criteria = $this->searchCriteriaBuilder->create();
        $list = $this->cityRepository->getList($criteria);
        $result = [];
        /** @var \Perspective\NovaposhtaCatalog\Api\Data\CityInterface $item */
        foreach ($list->getItems() as $item) {
            $result [] = [
                'id' => $item->getRef(),
                'text' => $item->getDescriptionUa(),
            ];
        }
        return $result;
    }

}
