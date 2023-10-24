<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields;

use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Hidden;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Controls\Select2;

class Street extends \Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form\Fields\AbstractSelectField
{

    public const NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID = 'novaposhtashipping_street';

    public const NOVAPOSHTA_SHIPPING_HIDDEN_INPUT_ID = 'novaposhtashipping_street_hidden';

    /**
     * @param \Magento\Framework\Data\Form $result
     * @return \Magento\Framework\Data\Form\Element\AbstractElement|null
     */
    public function createVisibleSelect(Form $result): ?\Magento\Framework\Data\Form
    {
        $result->getElement('main')->addField(
            static::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID,
            Select2::class,
            [],
            'city'
        );
        $element = $result->getElement(static::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
        $element->setData('label', __('NP Street'));
        $element->setData('name', static::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID);
//        $element->setData('required', true);
        $prefix = explode('-', $result->getHtmlIdPrefix() ?? '');
        if ($prefix && is_array($prefix) && count($prefix) > 1) {
            if ($prefix[1] == 'billing_address_') {
                $dataBindArray['scope'] = '\'streetInputAutocompleteBilling\'';
                $element->addClass('streetInputAutocompleteBillingClass');
            }
            if ($prefix[1] == 'shipping_address_') {
                $dataBindArray['scope'] = '\'streetInputAutocompleteShipping\'';
                $element->addClass('streetInputAutocompleteShippingClass');
            }
        }
        else {
            $dataBindArray['scope'] = '\'streetInputAutocompleteOrphan\'';
            $element->addClass('streetInputAutocompleteOrphanClass');
        }
        $element->setDataBind($dataBindArray);

        return $result;
    }
}
