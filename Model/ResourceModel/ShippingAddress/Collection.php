<?php

namespace Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection implements SearchResultInterface
{

    protected $aggregations;

    protected function _construct()
    {
        $this->_init(
            \Perspective\NovaposhtaShipping\Model\ShippingAddress::class,
            \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress::class
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
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
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
