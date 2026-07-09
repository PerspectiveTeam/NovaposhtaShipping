<?php

namespace Perspective\NovaposhtaShipping\Model\Quote\Info\Type;

use Magento\Framework\App\Request\DataPersistorInterface;
use Perspective\NovaposhtaShipping\Api\Data\ProcessorInterface;
use Perspective\NovaposhtaShipping\Block\Checkout\LayoutProcessor;

class AreaProcessor implements ProcessorInterface
{
    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    public function __construct(
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * @inheritDoc
     */
    public function process($cartId, $addressInformation)
    {
        $shippingAddress = $addressInformation->getShippingAddress();
        $extensionAttributes = $shippingAddress->getExtensionAttributes();
        $areaRef = $extensionAttributes->getPerspectiveNovaposhtaShippingArea() ?? '';

        $previousAreaRef = $this->dataPersistor->get(LayoutProcessor::AREA_NOVAPOSHTA_FIELD) ?? '';
        $this->dataPersistor->set(LayoutProcessor::AREA_NOVAPOSHTA_FIELD, $areaRef);

        if ($areaRef !== $previousAreaRef) {
            $this->dataPersistor->set(LayoutProcessor::CITY_NOVAPOSHTA_FIELD, '');
        }
    }

    /**
     * @inheritDoc
     */
    public function isApplicable($cartId, $addressInformation)
    {
        return true;
    }
}
