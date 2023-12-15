<?php

namespace Perspective\NovaposhtaShipping\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BoxShippingVisualisation extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'perspective_novaposhta_shipping_visualisation_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('perspective_novaposhta_shipping_visualisation', 'id');
        $this->_useIsObjectNew = true;
    }
}
