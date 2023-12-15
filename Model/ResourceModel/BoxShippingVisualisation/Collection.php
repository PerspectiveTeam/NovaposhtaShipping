<?php

namespace Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Perspective\NovaposhtaShipping\Model\BoxShippingVisualisation as Model;
use Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'perspective_novaposhta_shipping_visualisation_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
