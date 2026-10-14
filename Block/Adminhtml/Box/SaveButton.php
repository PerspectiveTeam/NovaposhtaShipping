<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Box;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Save Box'),
            'class' => 'save primary',
            'sort_order' => 90,
            'data_attribute' => [
                'mage-init' => ['buttonAdapter' => ['actions' => [[
                    'targetName' => 'novaposhta_box_form.novaposhta_box_form',
                    'actionName' => 'save',
                    'params' => [true],
                ]]]],
            ],
        ];
    }
}
