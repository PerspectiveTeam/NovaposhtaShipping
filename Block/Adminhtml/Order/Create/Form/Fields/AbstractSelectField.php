<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields;

use Magento\Framework\Data\Form;

abstract class AbstractSelectField
{
    public const NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID = '';

    public const NOVAPOSHTA_SHIPPING_HIDDEN_INPUT_ID = '';

    abstract public function createVisibleSelect(Form $result);

}
