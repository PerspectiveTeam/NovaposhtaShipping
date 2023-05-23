<?php


namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order;

use Magento\Framework\Locale\Resolver;

/**
 * Class NovaposhtaOnAdminChangeAddress
 * Class that DI another class that uses in template with this block
 */
class NovaposhtaOnAdminChangeAddress extends \Magento\Backend\Block\Template
{
    /**
     * @var \Perspective\NovaposhtaShipping\Block\Adminhtml\Order\NovaposhtaDeliveryInfo
     */
    public $helperNovaposhta;
    /**
     * @var
     */
    protected $address;
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;
    /**
     * @var \Magento\Backend\Block\Template\Context
     */
    private $context;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var array
     */
    private $data;

    /**
     * NovaposhtaOnAdminCreation constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Perspective\NovaposhtaShipping\Block\Adminhtml\Order\NovaposhtaDeliveryInfo $helperNovaposhta
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Perspective\NovaposhtaShipping\Block\Adminhtml\Order\NovaposhtaDeliveryInfo $helperNovaposhta,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->context = $context;
        $this->registry = $registry;
        $this->data = $data;
        $this->helperNovaposhta = $helperNovaposhta;
    }

    /**
     * @return string
     */
    public function getEnabled()
    {

        return $this->config->getIsEnabledConfig();
    }

    /**
     *
     */
    public function getAddress()
    {
        $this->address = $this->registry->registry('order_address');
    }

    public function getOrderId()
    {
        return $this->registry->registry('order_address')->getParentId();
    }
}
