<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Box;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Toggle extends AbstractBox implements HttpPostActionInterface
{
    public function execute()
    {
        try {
            $id = $this->readId();
            $enabled = $this->getRequest()->getParam('enabled');
            if ($id === null || !in_array($enabled, ['0', '1', 0, 1], true)) {
                throw new LocalizedException(__('Invalid box data.'));
            }
            $this->management->setEnabled($id, (bool)$enabled);
            $this->messageManager->addSuccessMessage(__('BoxPacker availability has been updated.'));
        } catch (\Throwable $error) {
            $this->reportError($error);
        }
        return $this->resultRedirectFactory->create()->setPath(self::GRID_ROUTE);
    }
}
