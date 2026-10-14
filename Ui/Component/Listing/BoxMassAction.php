<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Ui\Component\Listing;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\MassAction;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\AbstractBox;

class BoxMassAction extends MassAction
{
    public function __construct(
        ContextInterface $context,
        private AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    public function prepare(): void
    {
        if (!$this->authorization->isAllowed(AbstractBox::ADMIN_RESOURCE)) {
            $this->components = [];
            $this->setData('config', array_replace($this->getConfiguration(), ['actions' => [], 'visible' => false]));
        }
        parent::prepare();
    }
}
