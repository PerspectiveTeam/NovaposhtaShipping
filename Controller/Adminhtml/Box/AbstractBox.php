<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Box;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Perspective\NovaposhtaShipping\Api\BoxManagementInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractBox extends Action
{
    public const ADMIN_RESOURCE = 'Perspective_NovaposhtaShipping::boxes';
    public const GRID_ROUTE = 'novaposhtacatalog/view/packagetypesdata';
    public const PERSISTOR_KEY = 'novaposhta_box';

    public function __construct(
        Context $context,
        protected BoxManagementInterface $management,
        protected DataPersistorInterface $dataPersistor,
        protected LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    protected function readId(string $name = 'id'): ?int
    {
        $value = $this->getRequest()->getParam($name);
        if ($value === null || $value === '') {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new LocalizedException(__('Invalid box ID.'));
        }
        return $id;
    }

    protected function reportError(\Throwable $error): void
    {
        if ($error instanceof LocalizedException) {
            $this->messageManager->addErrorMessage($error->getMessage());
        } else {
            $this->logger->error('Nova Poshta box management failed.', ['exception' => $error]);
            $this->messageManager->addErrorMessage(__('The box operation failed. Please try again.'));
        }
    }
}
