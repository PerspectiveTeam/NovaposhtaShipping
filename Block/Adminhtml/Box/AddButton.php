<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Box;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\AbstractBox;

class AddButton implements ButtonProviderInterface
{
    public function __construct(private UrlInterface $urlBuilder, private AuthorizationInterface $authorization)
    {
    }

    public function getButtonData(): array
    {
        if (!$this->authorization->isAllowed(AbstractBox::ADMIN_RESOURCE)) {
            return [];
        }
        return [
            'label' => __('New Box'),
            'class' => 'add primary',
            'url' => $this->urlBuilder->getUrl('novaposhtashipping/box/edit'),
        ];
    }
}
