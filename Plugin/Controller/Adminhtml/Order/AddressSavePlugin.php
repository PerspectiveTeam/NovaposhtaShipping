<?php

namespace Perspective\NovaposhtaShipping\Plugin\Controller\Adminhtml\Order;

use Exception;
use Magento\Framework\Exception\AbstractAggregateException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\AddressSave;
use Magento\Sales\Model\Order\Address as AddressModel;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache;
use Perspective\NovaposhtaShipping\Plugin\Controller\Adminhtml\Order\AddressSave\GetSpecifiedCartModel;

class AddressSavePlugin
{
    private OrderAddressRepositoryInterface $orderAddressRepository;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private CityRepositoryInterface $cityRepository;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface
     */
    private WarehouseRepositoryInterface $warehouseRepository;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private StreetRepositoryInterface $streetRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingCheckoutOnestepPriceCacheInterfaceFactory
     */
    private ShippingCheckoutOnestepPriceCacheInterfaceFactory $checkoutOnestepPriceCacheFactory;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\ShippingCheckoutOnestepPriceCache
     */
    private ShippingCheckoutOnestepPriceCache $checkoutOnestepPriceCacheResourceModel;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Plugin\Controller\Adminhtml\Order\AddressSave\GetSpecifiedCartModel
     */
    private GetSpecifiedCartModel $getSpecifiedCartModel;

    /**
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface $warehouseRepository
     * @param \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface $streetRepository
     */
    public function __construct(
        OrderAddressRepositoryInterface $orderAddressRepository,
        CityRepositoryInterface $cityRepository,
        WarehouseRepositoryInterface $warehouseRepository,
        StreetRepositoryInterface $streetRepository,
        GetSpecifiedCartModel $getSpecifiedCartModel

    ) {
        $this->orderAddressRepository = $orderAddressRepository;
        $this->cityRepository = $cityRepository;
        $this->warehouseRepository = $warehouseRepository;
        $this->streetRepository = $streetRepository;
        $this->getSpecifiedCartModel = $getSpecifiedCartModel;
    }

    /**
     * Возвращаем null в любой непонятной ситуации
     * @param AddressSave $subject
     * @return null
     */
    public function beforeExecute(AddressSave $subject)
    {
        try {
            $cityRef = $subject->getRequest()->getParam('novaposhta_city') ?: null;
            $streetRef = $subject->getRequest()->getParam('novaposhta_street') ?: null;
            $warehouseRef = $subject->getRequest()->getParam('novaposhta_warehouse') ?: null;
            try {
                /** @var $address OrderAddressInterface|AddressModel */
                $address = $this->orderAddressRepository->get($subject->getRequest()->getParam('address_id'));
                $arrayToSave = [
                    'city' => $cityRef,
                    'street' => $streetRef,
                    'warehouse' => $warehouseRef,
                ];
                $address = $this->setRefData($address, $arrayToSave);
                $city = $this->cityRepository->getCityByCityRef($cityRef ?? '');
                $street = $this->streetRepository->getObjectByRef($streetRef ?? '');
                $warehouse = $this->warehouseRepository->getWarehouseByWarehouseRef($warehouseRef ?? '');
                $arrayToSave['city'] = $city ? $city->getDescriptionUa() : null;
                $arrayToSave['street'] = $street ? $street->getDescription() : null;
                $arrayToSave['warehouse'] = $warehouse ? $warehouse->getDescriptionUa() : null;
                $address = $this->setHumanReadableData($address, $arrayToSave, $subject);
            } catch (NoSuchEntityException $e) {
                return null;
            }

            if ($address->getId() && $address->getParentId()) {
                try {
                    $this->orderAddressRepository->save($address);
                } catch (AbstractAggregateException $e) {
                    return null;
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function setRefData($address, $dataArr)
    {
        $carrierDataModelInfo = $this->getSpecifiedCartModel->execute($address);
        foreach ($dataArr as $key => $value) {
            if (!empty($value)) {
                $address->setData('perspective_novaposhta_shipping_' . $key, $value);
                if ($key == 'city' || $key == 'warehouse') {
                    $carrierDataModelInfo->setData($key . '_id', $value);

                } else {
                    $carrierDataModelInfo->setData($key, $value);
                }
            }
        }
        $carrierDataModelInfo->save();
        return $address;
    }


    private function setHumanReadableData($address, $dataArr, $subject)
    {
        foreach ($dataArr as $key => $value) {
            if (!empty($value)) {
                $address->setData($key, $value);
                $subject->getRequest()->setParam($key, $value);
            }
        }
        return $address;
    }
}
