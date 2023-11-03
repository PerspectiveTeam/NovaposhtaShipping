<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create;

use Exception;
use Magento\Backend\Model\Session\Quote;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Order\NovaposhtaDeliveryInfo;

class AutocompleteScripts extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    private NovaposhtaDeliveryInfo $novaposhtaDeliveryInfo;

    private Registry $registry;

    private Quote $sessionQuote;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     * @param \Magento\Framework\Json\Helper\Data|null $jsonHelper
     * @param \Magento\Directory\Helper\Data|null $directoryHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        StoreManagerInterface $storeManager,
        NovaposhtaDeliveryInfo $novaposhtaDeliveryInfo,
        Registry $registry,
        Quote $sessionQuote,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->storeManager = $storeManager;
        $this->novaposhtaDeliveryInfo = $novaposhtaDeliveryInfo;
        $this->registry = $registry;
        $this->sessionQuote = $sessionQuote;
    }

    public function getData($key = '', $index = null)
    {
        try {
            if ($this->registry->registry('current_order') === null) {
                $this->registry->register('current_order', $this->sessionQuote->getOrder());
            }
            $shippingInfo = $this->novaposhtaDeliveryInfo->getShipping();
            if ($shippingInfo && $shippingInfo->getData()) {
                if (in_array($key, array_keys($shippingInfo->getData()))) {
                    return $shippingInfo->getData($key);
                } else if (str_ends_with($key, 'label')) {
                    $realKey = str_replace('_label', '', $key);
                    return $this->novaposhtaDeliveryInfo->decorateValue($realKey, $shippingInfo->getData($realKey) ?? '');
                }
            }
        } catch (Exception $e) {
            return parent::getData($key, $index);
        }
    }

    /**
     * @param string $path
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRestUrl(string $path)
    {
        return $this->storeManager->getStore(1)->getBaseUrl() . $path;
    }
}
