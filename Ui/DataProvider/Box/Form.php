<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Ui\DataProvider\Box;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\CollectionFactory;
use Perspective\NovaposhtaShipping\Api\BoxManagementInterface;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\AbstractBox;

class Form extends AbstractDataProvider
{
    private ?array $loadedData = null;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private BoxManagementInterface $management,
        private RequestInterface $request,
        private DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }
        $id = (int)$this->request->getParam('id');
        $duplicateId = (int)$this->request->getParam('duplicate_id');
        if ($id) {
            $data = $this->management->getDuplicateData($id);
            $data['id'] = $id;
        } elseif ($duplicateId) {
            $data = $this->management->getDuplicateData($duplicateId);
            $data['duplicate_id'] = $duplicateId;
        } else {
            $data = ['boxpacker_enabled' => 1, 'empty_weight' => 0];
        }
        $persisted = $this->dataPersistor->get(AbstractBox::PERSISTOR_KEY);
        if (is_array($persisted)
            && (int)($persisted['id'] ?? 0) === $id
            && (int)($persisted['duplicate_id'] ?? 0) === $duplicateId
        ) {
            $data = array_replace($data, array_intersect_key($persisted, array_flip([
                'description_ua', 'length', 'width', 'height', 'max_weight', 'boxpacker_enabled',
                'inner_length', 'inner_width', 'inner_height', 'empty_weight',
            ])));
        }
        $this->dataPersistor->clear(AbstractBox::PERSISTOR_KEY);
        $data['boxpacker_enabled'] = (string)(int)$data['boxpacker_enabled'];
        return $this->loadedData = [$id ?: '' => $data];
    }
}
