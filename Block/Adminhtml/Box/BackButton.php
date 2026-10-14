<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Box;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\AbstractBox;

class BackButton implements ButtonProviderInterface
{
    public function __construct(private UrlInterface $urlBuilder)
    {
    }

    public function getButtonData(): array
    {
        return [
            'label' => __('Back'),
            'class' => 'back',
            'url' => $this->urlBuilder->getUrl(AbstractBox::GRID_ROUTE),
            'sort_order' => 10,
        ];
    }
}
