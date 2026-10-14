<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Plugin\Block\System\Config;

use Magento\Config\Block\System\Config\Tabs;
use Magento\Config\Model\Config\Structure\Element\Section;

class ShippingLink
{
    /**
     * Link the Perspective navigation entry to the existing carrier settings.
     *
     * @param Tabs $subject
     * @param string $result
     * @param Section $section
     * @return string
     */
    public function afterGetSectionUrl(Tabs $subject, string $result, Section $section): string
    {
        if ($section->getId() !== 'novaposhta_shipping') {
            return $result;
        }

        return $subject->getUrl('adminhtml/system_config/edit', [
            '_current' => true,
            'section' => 'carriers',
            '_fragment' => 'carriers_novaposhtashipping-link',
        ]);
    }
}
