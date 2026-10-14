<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Box;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Edit extends AbstractBox implements HttpGetActionInterface
{
    public function execute()
    {
        try {
            $id = $this->readId();
            $duplicateId = $this->readId('duplicate_id');
            if ($id !== null && $duplicateId !== null) {
                throw new LocalizedException(__('Invalid box ID.'));
            }
            if ($id !== null && $this->management->getById($id)->getSource() !== 'custom') {
                throw new LocalizedException(__('Only custom boxes can be edited.'));
            }
            if ($duplicateId !== null) {
                $this->management->getById($duplicateId);
            }
            $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $page->getConfig()->getTitle()->prepend($id !== null ? __('Edit Box') : __('New Box'));
            return $page;
        } catch (\Throwable $error) {
            $this->reportError($error);
            return $this->resultRedirectFactory->create()->setPath(self::GRID_ROUTE);
        }
    }
}
