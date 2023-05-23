<?php

namespace Perspective\NovaposhtaShipping\Block\Checkout;

class SaveAddressInformationProcessor
{
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $processor,
        $jsLayout
    ) {
        $customAttributeCode = 'perspective_novaposhta_shipping_city';
        $customField = [
            'component' => 'Magento_Ui/js/form/element/select',
            'config' => [
                // customScope is used to group elements within a single form (e.g. they can be validated separately)
                'customScope' => 'shippingAddress.custom_attributes.perspective_novaposhta_shipping',
                'customEntry' => null,
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
                /*   'tooltip' => [
                       'description' => __('Novaposhta City'),
                   ],*/
                'options' => [
                    [
                        'label' => 'label',
                        'value' => 'value',
                    ],
                ],
            ],
            'dataScope' => 'shippingAddress.custom_attributes' . '.' . $customAttributeCode,
            'label' => __('Novaposhta City'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 152,
            'validation' => [
                'required-entry' => false
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'value' => '' // value field is used to set a default value of the attribute
        ];
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $customField;
        return $jsLayout;
    }
}
