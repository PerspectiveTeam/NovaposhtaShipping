<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Perspective\NovaposhtaShipping\Model\Config\Source;

use Magento\Shipping\Model\Carrier\Source\GenericInterface;

/**
 * Class Method
 */
class Method implements GenericInterface
{
    /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arr = [];

        $arr[] = ['value' => 'w2w', 'label' => __('Warehouse to Warehouse')];
        $arr[] = ['value' => 'w2c', 'label' => __('Warehouse to Client')];
        $arr[] = ['value' => 'c2w', 'label' => __('Client to Warehouse')];
        $arr[] = ['value' => 'c2c', 'label' => __('Client to Client')];

        return $arr;
    }
}
