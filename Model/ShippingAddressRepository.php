<?php

namespace Perspective\NovaposhtaShipping\Model;

use Exception;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\ShippingAddressRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress as ResourceModel;
use Perspective\NovaposhtaShipping\Model\ShippingAddress as Model;
use Perspective\NovaposhtaShipping\Model\ShippingAddressFactory as ModelFactory;
use Psr\Log\LoggerInterface as Logger;

class ShippingAddressRepository extends ShippingRepository implements ShippingAddressRepositoryInterface
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
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private CartRepositoryInterface $cartRepository;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private CityRepositoryInterface $cityRepository;

    private StreetRepositoryInterface $streetRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private Logger $logger;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteIdMaskResource $quoteIdMaskResource,
        ModelFactory $modelFactory,
        ResourceModel $resourceModel,
        CartRepositoryInterface $cartRepository,
        CityRepositoryInterface $cityRepository,
        StreetRepositoryInterface $streetRepository,
        Logger $logger
    ) {
        $this->modelFactory = $modelFactory;
        $this->resourceModel = $resourceModel;
        $this->cartRepository = $cartRepository;
        $this->cityRepository = $cityRepository;
        $this->streetRepository = $streetRepository;
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
                $streetRef =
                    $this->streetRepository->getCollectionByCityRef($cityRef)->getFirstItem()->getRef()
                    ?? '0f0d85b0-4143-11dd-9198-001d60451983';
                $model->setStreet($streetRef);
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage(), ['trace' => $e->getMessage()]);
            }
            $model->setBuilding('1');
            $model->setFlat('1');
        }
        return $model;
    }
}
