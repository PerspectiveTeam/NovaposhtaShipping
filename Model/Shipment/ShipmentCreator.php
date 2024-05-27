<?php

namespace Perspective\NovaposhtaShipping\Model\Shipment;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Perspective\NovaposhtaShipping\Api\Data\OrderShippmentProcessorInterface;

/**
 * This class is supposed to be abstract but
 * due to bug with Virtual Types in Magento
 * it needs to stay normal. Otherwise virtual classes
 * aren't instantiated
 */
class ShipmentCreator
{
    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\OrderShippmentProcessorInterface[]
     */
    protected $processors;

    /**
     * @param array<mixed> $data
     * @throws LocalizedException
     */
    public function __construct(
        array $data = []
    ) {
        foreach ($data as $processor) {
            if (!$processor instanceof OrderShippmentProcessorInterface) {
                throw new LocalizedException(
                    __('Processors must implement ShippingProcessorInterface.')
                );
            }
        }
        $this->processors = $data;
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return array
     */
    public function process(
        $object
    ) {
        foreach ($this->processors as $processor) {
            if ($processor->isApplicable($object)) {
                return $processor->doInternetDocument($object);
            }
        }
        $result['data'] = [];
        return $result;
    }
}
