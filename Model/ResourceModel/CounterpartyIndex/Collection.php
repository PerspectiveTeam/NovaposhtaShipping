<?php

namespace Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndex;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyIndexInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndex;

class Collection extends AbstractCollection implements SearchResultInterface
{

    protected $aggregations;

    protected function _construct()
    {
        $this->_init(
            \Perspective\NovaposhtaShipping\Model\CounterpartyIndex::class,
            CounterpartyIndex::class
        );
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @inheritDoc
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * @inheritDoc
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @inheritDoc
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }
}
