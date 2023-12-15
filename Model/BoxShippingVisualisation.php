<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Model\AbstractModel;
use Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation as ResourceModel;

class BoxShippingVisualisation extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'perspective_novaposhta_shipping_visualisation_model';

    public function getBoxUrl()
    {
        return base64_decode($this->getData('box_url'));
    }

    public function setBoxUrl($boxUrl)
    {
        $this->setData('box_url', base64_encode($boxUrl));
    }

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
