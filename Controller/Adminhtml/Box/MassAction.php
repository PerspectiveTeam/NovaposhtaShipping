<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Box;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\CollectionFactory;
use Perspective\NovaposhtaShipping\Api\BoxManagementInterface;
use Psr\Log\LoggerInterface;

class MassAction extends AbstractBox implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        BoxManagementInterface $management,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger,
        private Filter $filter,
        private CollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $management, $dataPersistor, $logger);
    }

    public function execute()
    {
        $changed = $skipped = 0;
        try {
            $operation = $this->getRequest()->getParam('operation');
            if (!in_array($operation, ['enable', 'disable', 'delete'], true)) {
                throw new LocalizedException(__('Invalid box operation.'));
            }
            $packages = $this->filter->getCollection($this->collectionFactory->create());
            foreach ($packages as $package) {
                if ($operation === 'delete' && $package->getSource() !== 'custom') {
                    ++$skipped;
                    continue;
                }
                if ($operation === 'delete') {
                    $this->management->delete((int)$package->getId());
                } else {
                    $this->management->setEnabled((int)$package->getId(), $operation === 'enable');
                }
                ++$changed;
            }
        } catch (\Throwable $error) {
            $this->reportError($error);
        }
        $this->messageManager->addSuccessMessage(__('Updated %1 box(es); skipped %2 protected box(es).', $changed, $skipped));
        return $this->resultRedirectFactory->create()->setPath(self::GRID_ROUTE);
    }
}
