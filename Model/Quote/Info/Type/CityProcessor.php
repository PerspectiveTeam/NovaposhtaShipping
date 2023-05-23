<?php

namespace Perspective\NovaposhtaShipping\Model\Quote\Info\Type;

use Magento\Framework\App\Request\DataPersistorInterface;
use Perspective\NovaposhtaShipping\Api\Data\ProcessorInterface;
use Perspective\NovaposhtaShipping\Block\Checkout\LayoutProcessor;
use Perspective\NovaposhtaShipping\Model\Quote\Info\Session\QuoteObject;

class CityProcessor implements ProcessorInterface
{
    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
    }

    public function process($cartId, $addressInformation)
    {
        $shippingAddress = $addressInformation->getShippingAddress();
        $extensionAttributes = $shippingAddress->getExtensionAttributes();
        $this->dataPersistor->set(LayoutProcessor::CITY_NOVAPOSHTA_FIELD, $extensionAttributes->getPerspectiveNovaposhtaShippingCity() ?? '');
    }

    public function isApplicable($cartId, $addressInformation)
    {
        return true;
    }
}
