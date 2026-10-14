<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BoxOrigin implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'api', 'label' => __('Nova Poshta API')],
            ['value' => 'standard', 'label' => __('Standard')],
            ['value' => 'custom', 'label' => __('Custom')],
        ];
    }
}
