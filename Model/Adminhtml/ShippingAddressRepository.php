<?php

namespace Perspective\NovaposhtaShipping\Model\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Adminhtml\ShippingAddressRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress as ResourceModel;
use Perspective\NovaposhtaShipping\Model\ShippingAddressFactory as ModelFactory;
use Perspective\NovaposhtaShipping\Model\ShippingAddress as Model;

class ShippingAddressRepository implements ShippingAddressRepositoryInterface
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
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private $streetRepository;

    protected $isInfo = false;

    /**
     * @param \Perspective\NovaposhtaShipping\Model\ShippingAddressFactory $modelFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingAddress $resourceModel
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface $streetRepository
     */
    public function __construct(
        ModelFactory $modelFactory,
        ResourceModel $resourceModel,
        RequestInterface $request,
        StreetRepositoryInterface $streetRepository
    ) {
        $this->modelFactory = $modelFactory;
        $this->resourceModel = $resourceModel;
        $this->request = $request;
        $this->streetRepository = $streetRepository;
    }

    public function loadAddressInfo($quoteId)
    {
        /** @var Model $model */
        $model = $this->modelFactory->create();
        $this->resourceModel->load($model, $quoteId, 'cart_id');
        if (!$model->getId()) {

            //задаем дефолтные/значения значения
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
                //для расчета улица не важна - берем первую попавшуюся в этом городе
                $streetArr = $this->streetRepository->getByCityRef($cityRef)->getItems();
                $model->setStreet(reset($streetArr)->getRef());
            } else {
                $model->setCity('e221d64c-391c-11dd-90d9-001a92567626');
                $model->setStreet('29207dc8-7aa5-11df-be73-000c290fbeaa');
                if ($this->getIsInfo()) {
                    $model->setCity(null);
                    $model->setStreet(null);
                }
            }
            $model->setBuilding('1');
            $model->setFlat('1');
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
