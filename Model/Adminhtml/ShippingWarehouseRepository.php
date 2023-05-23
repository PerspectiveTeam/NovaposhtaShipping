<?php

namespace Perspective\NovaposhtaShipping\Model\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Adminhtml\ShippingWarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse as ResourceModel;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouseFactory as ModelFactory;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouse as Model;

class ShippingWarehouseRepository implements ShippingWarehouseRepositoryInterface
{
    /**
     * @var ModelFactory
     */
    private $modelFactory;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface
     */
    private $warehouseRepository;

    protected $isInfo;

    /**
     * @param \Perspective\NovaposhtaShipping\Model\ShippingWarehouseFactory $modelFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse $resourceModel
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface $warehouseRepository
     */
    public function __construct(
        ModelFactory $modelFactory,
        ResourceModel $resourceModel,
        RequestInterface $request,
        WarehouseRepositoryInterface $warehouseRepository
    ) {
        $this->modelFactory = $modelFactory;
        $this->resourceModel = $resourceModel;
        $this->request = $request;
        $this->warehouseRepository = $warehouseRepository;
    }

    public function loadAddressInfo($quoteId)
    {
        /** @var Model $model */
        $model = $this->modelFactory->create();
        $this->resourceModel->load($model, $quoteId, 'cart_id');
        if (!$model->getId()) {
            $order = $this->request->getParam('order');
            if ($order &&
                (
                    isset($order['billing_address']['novaposhta_city']) ||
                    isset($order['shipping_address']['novaposhta_city'])
                )
            ) {
                $cityRef = $order['shipping_address']['novaposhta_city'] ??
                    $order['billing_address']['novaposhta_city'];
            }

            if (!empty($cityRef)) {
                $model->setCity($cityRef);
                //для расчета склад не важен
                $warehouseCollection = $this->warehouseRepository->getArrayOfWarehouseModelsByCityRef(
                    $cityRef,
                    'uk_UA'
                );
                $model->setWarehouse(reset($warehouseCollection)->getRef());
            } else {
                //задаем дефолтные значения
                $model->setCity('e221d64c-391c-11dd-90d9-001a92567626');
                $model->setWarehouse('e221d64c-391c-11dd-90d9-001a92567626');
                if ($this->getIsInfo()) {
                    $model->setCity(null);
                    $model->setWarehouse(null);
                }
            }
        }
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getIsInfo()
    {
        return $this->isInfo;
    }

    /**
     * @inheritDoc
     */
    public function setIsInfo($isInfo): void
    {
        $this->isInfo = $isInfo;
    }
}
