<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Box;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete extends AbstractBox implements HttpPostActionInterface
{
    public function execute()
    {
        try {
            $id = $this->readId();
            if ($id === null) {
                throw new LocalizedException(__('Invalid box ID.'));
            }
            $this->management->delete($id);
            $this->messageManager->addSuccessMessage(__('The box has been deleted.'));
        } catch (\Throwable $error) {
            $this->reportError($error);
        }
        return $this->resultRedirectFactory->create()->setPath(self::GRID_ROUTE);
    }
}
