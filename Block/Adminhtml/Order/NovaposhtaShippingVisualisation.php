<?php


namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Config;
use Perspective\NovaposhtaShipping\Model\Carrier\Mapping;
use Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation\CollectionFactory;

/**
 * Class NovaposhtaDeliveryInfo
 * Block for novaposhta info in order
 */
class NovaposhtaShippingVisualisation extends NovaposhtaDeliveryInfo
{
    private $forbiddenKeys = [
        'id',
        'cart_id',
        'created_at',
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
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation\CollectionFactory
     */
    private CollectionFactory $boxShippingVisualisationCollectionFactory;

    /**
     * NovaposhtaDeliveryInfo constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Mapping $carrierMapping
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Perspective\NovaposhtaCatalog\Api\StreetRepositoryInterface $streetRepository
     * @param \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface $warehouseRepository
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
        CollectionFactory $boxShippingVisualisationCollectionFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $config,
            $orderRepository,
            $registry,
            $carrierMapping,
            $cityRepository,
            $streetRepository,
            $warehouseRepository,
            $data
        );
        $this->boxShippingVisualisationCollectionFactory = $boxShippingVisualisationCollectionFactory;
    }

    public function allowedToShow($key, $value)
    {
        $result = false;
        if (strpos($key, 'box_url_') !== false) {
            $result = true;
        }
        if (!in_array($key, $this->forbiddenKeys)) {
            $result = true;
        }
        if (empty($value)) {
            $result = false;
        }
        return $result;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed|string|null
     */
    public function decorateValue($key, $value)
    {
        /** @var \Perspective\NovaposhtaShipping\Model\BoxShippingVisualisation $result */
        $result = $value;
        if (strpos($key, 'box_url_') !== false) {
            $boxNumber = str_replace('box_url_', '', $key);
            $result = $result->getBoxUrl();// '<a href="' . $result->getBoxUrl() . '">' . __('Visualise Box #%1', $boxNumber) . '</a>';
        } else {
            $result = '';
        }
        return $result;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed|string|null
     */
    public function decorateLabel($key)
    {
        /** @var \Perspective\NovaposhtaShipping\Model\BoxShippingVisualisation $result */
        if (strpos($key, 'box_url_') !== false) {
            $boxNumber = str_replace('box_url_', '', $key);
            $result = __('Box #%1', $boxNumber);
        } else {
            $result = '';
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
        $boxShippingVisualisationCollection = $this->boxShippingVisualisationCollectionFactory->create();
        $boxShippingVisualisationCollection->addFieldToFilter('cart_id', $this->getQuoteId());
        $i = 0;
        $shippingVisualiserArray = [];
        foreach ($boxShippingVisualisationCollection->getIterator() as $item) {
            $i++;
            $shippingVisualiserArray['box_url_' . $i] = $item;
        }
        $this->shippingInfo = new DataObject($shippingVisualiserArray);
        return $this->shippingInfo;
    }

    public function getBoxNumber($key)
    {
        return str_replace('box_url_', '', $key);
    }
}
