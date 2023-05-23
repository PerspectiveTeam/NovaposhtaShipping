<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\ShippingWarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse as ResourceModel;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouse as Model;
use Perspective\NovaposhtaShipping\Model\ShippingWarehouseFactory as ModelFactory;
use Psr\Log\LoggerInterface as Logger;
use Throwable;

class ShippingWarehouseRepository extends ShippingRepository implements ShippingWarehouseRepositoryInterface
{
    /**
     * @var ModelFactory
     */
    private $modelFactory;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    private CartRepositoryInterface $cartRepository;

    private CityRepositoryInterface $cityRepository;

    private WarehouseRepositoryInterface $warehouseRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private Logger $logger;

    /**
     * @param \Perspective\NovaposhtaShipping\Model\ShippingWarehouseFactory $modelFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingWarehouse $resourceModel
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface $warehouseRepository
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteIdMaskResource $quoteIdMaskResource,
        ModelFactory $modelFactory,
        ResourceModel $resourceModel,
        CartRepositoryInterface $cartRepository,
        CityRepositoryInterface $cityRepository,
        WarehouseRepositoryInterface $warehouseRepository,
        Logger $logger
    ) {
        $this->modelFactory = $modelFactory;
        $this->resourceModel = $resourceModel;
        $this->cartRepository = $cartRepository;
        $this->cityRepository = $cityRepository;
        $this->warehouseRepository = $warehouseRepository;
        $this->logger = $logger;
        parent::__construct($quoteIdMaskFactory, $quoteIdMaskResource);
    }

    public function loadAddressInfo($quoteId)
    {
        $quoteId = $this->resolveCartId($quoteId);
        /** @var Model $model */
        $model = $this->modelFactory->create();
        $this->resourceModel->load($model, $quoteId, 'cart_id');
        if (!$model->getId()) {
            //задаем дефолтные значения
            $quote = $this->cartRepository->get($quoteId);
            $cityName = $quote->getShippingAddress()->getCity() ?? 'Київ';
            try {
                $cityRef =
                    $this->cityRepository->getCityCollectionByName($cityName)->getFirstItem()->getRef()
                    ?? '8d5a980d-391c-11dd-90d9-001a92567626';
                $model->setCity($cityRef);
                $warehouseRef =
                    $this->warehouseRepository->getCollectionOfWarehousesByCityRef($cityRef)->getFirstItem()->getRef()
                    ?? '1ec09d88-e1c2-11e3-8c4a-0050568002cf';
                $model->setWarehouse($warehouseRef);
            } catch (Throwable $e) {
                //только логируем ошибку
                $this->logger->critical($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        }
        return $model;
    }
}
