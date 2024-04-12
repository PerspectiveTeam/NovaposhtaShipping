<?php

namespace Perspective\NovaposhtaShipping\Model\SenderRepository;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory;

class SenderContactPersonAddress
{
    private CollectionFactory $collectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function get($term = null)
    {
        $result [] = [
            'id' => 'TODO ref',
            'text' => 'TODO name',
        ];
        return $result;
    }
}
