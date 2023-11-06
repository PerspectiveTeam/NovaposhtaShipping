<?php


namespace Perspective\NovaposhtaShipping\Block\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;

/**
 * Class NovaposhtaDeliveryInfo
 * Block for novaposhta info in order
 */
class NovaposhtaDeliveryInfo extends Template
{
    private $forbiddenKeys = [
        'id',
        'cart_id',
        'updated_at',
    ];

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\Mapping
     */
    private $carrierMapping;

    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\GeneralShippingInterface
     */
    private $shippingInfo;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private $cityRepository;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface
     */
    private $streetRepository;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface
     */
    private $warehouseRepository;

    /**
     * NovaposhtaDeliveryInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        OrderRepositoryInterface $orderRepository,
        Registry $registry,
        Mapping $carrierMapping,
        CityRepositoryInterface $cityRepository,
        StreetRepositoryInterface $streetRepository,
        WarehouseRepositoryInterface $warehouseRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
        $this->carrierMapping = $carrierMapping;
        $this->cityRepository = $cityRepository;
        $this->streetRepository = $streetRepository;
        $this->warehouseRepository = $warehouseRepository;
    }

    public function allowedToShow($key, $value)
    {
        $result = false;
        if (!in_array($key, $this->forbiddenKeys)) {
            $result = true;
        }
        if (empty($value)) {
            $result = false;
        }
        return $result;
    }

    public function decorateValue($key, string $value)
    {
        $result = $value;
        if ($key === 'city' || $key === 'city_id') {
            $model = $this->cityRepository->getCityByCityRef($value);
            $result = $model->getDescriptionUa() ?: $model->getDescriptionRu();
        }
        if ($key === 'street') {
            $model = $this->streetRepository->getByRef($value);
            $streetArray = $model->getItems();
            if (count($streetArray) === 0) {
                $result = $value;
            } else {
                $result = reset($streetArray)->getDescription();
            }
        }
        if ($key === 'warehouse_id') {
            $model = $this->warehouseRepository->getWarehouseByWarehouseRef($value);
            $result = $model->getDescriptionUa() ?: $model->getDescriptionRu();
        }
        return $result;
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Api\Data\GeneralShippingInterface
     */
    public function getShipping()
    {
        if ($this->shippingInfo) {
            return $this->shippingInfo;
        }
        foreach ($this->carrierMapping->getShippingMethodClasses() as $shippingMethod) {
            $shippingInfo = $shippingMethod->loadAddressInfo($this->getQuoteId());
            if ($shippingInfo->getId()) {
                $this->shippingInfo = $shippingInfo;
                break;
            }
        }
        return $this->shippingInfo;
    }

    /**
     * @return int|null
     */
    protected function getQuoteId()
    {
        return $this->getOrder()->getQuoteId();
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     * Бывает, что в реквесте не будет ордер_ид, тогда берем из Регистра
     * И вроде как этот прикол нужен для создания заказа в админке
     */
    protected function getOrder()
    {
        $order_id_from_request = $this->getRequest()->getParam('order_id');
        $order_id_from_registry = $this->registry->registry('order_address')
            ? $this->registry->registry('order_address')->getParentId()
            : null;
        $order_id_reorder = $this->registry->registry('current_order')
            ? $this->registry->registry('current_order')->getId()
            : null;
        $order_id = $order_id_from_request ?: $order_id_from_registry ?: $order_id_reorder;
        return $this->orderRepository->get($order_id);
    }

    /**
     * @return string
     */
    public function getEnabled()
    {
        $enabled = $this->config->getIsEnabledConfig($this->getOrder()->getStoreId());
        $applicable = $this->getShipping();
        return $enabled && $applicable;
    }
}
