<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Ui\Component\Listing\Column;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\AbstractBox;

class BoxActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private UrlInterface $urlBuilder,
        private AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!$this->authorization->isAllowed(AbstractBox::ADMIN_RESOURCE)) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] ?? [] as $index => $item) {
            $id = (int)$item['id'];
            $enabled = (int)$item['boxpacker_enabled'] === 1;
            $actions = [
                'duplicate' => [
                    'href' => $this->urlBuilder->getUrl('novaposhtashipping/box/edit', ['duplicate_id' => $id]),
                    'label' => __('Duplicate'),
                ],
                'toggle' => [
                    'href' => $this->urlBuilder->getUrl('novaposhtashipping/box/toggle', ['id' => $id, 'enabled' => (int)!$enabled]),
                    'label' => $enabled ? __('Disable for BoxPacker') : __('Enable for BoxPacker'),
                    'post' => true,
                ],
            ];
            if ($item['source'] === 'custom') {
                $actions['edit'] = [
                    'href' => $this->urlBuilder->getUrl('novaposhtashipping/box/edit', ['id' => $id]),
                    'label' => __('Edit'),
                ];
                $actions['delete'] = [
                    'href' => $this->urlBuilder->getUrl('novaposhtashipping/box/delete', ['id' => $id]),
                    'label' => __('Delete'),
                    'post' => true,
                    'confirm' => [
                        'title' => __('Delete Box'),
                        'message' => __('Are you sure you want to delete this custom box?'),
                    ],
                ];
            }
            $dataSource['data']['items'][$index][$this->getData('name')] = $actions;
        }
        return $dataSource;
    }
}
