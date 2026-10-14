<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Box;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends AbstractBox implements HttpPostActionInterface
{
    public function execute()
    {
        $id = $duplicateId = null;
        $data = $this->getRequest()->getPostValue();
        try {
            $id = $this->readId();
            $duplicateId = $this->readId('duplicate_id');
            if (!is_array($data) || ($id !== null && $duplicateId !== null)) {
                throw new LocalizedException(__('Invalid box data.'));
            }
            if ($duplicateId !== null) {
                $this->management->duplicate($duplicateId, $data);
            } else {
                $this->management->save($data, $id);
            }
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
            $this->messageManager->addSuccessMessage(__('The box has been saved.'));
            return $this->resultRedirectFactory->create()->setPath(self::GRID_ROUTE);
        } catch (\Throwable $error) {
            $this->reportError($error);
            if (is_array($data)) {
                $this->dataPersistor->set(self::PERSISTOR_KEY, $data);
            }
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', [
                'id' => $id,
                'duplicate_id' => $duplicateId,
            ]);
        }
    }
}
