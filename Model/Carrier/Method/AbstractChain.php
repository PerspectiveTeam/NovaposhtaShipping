<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier\Method;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Perspective\NovaposhtaShipping\Api\Data\ShippingProcessorInterface;

/**
 * This class is supposed to be abstract but
 * due to bug with Virtual Types in Magento
 * it needs to stay normal. Otherwise virtual classes
 * aren't instantiated
 */
class AbstractChain
{
    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\ShippingProcessorInterface[]
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
            if (!$processor instanceof ShippingProcessorInterface) {
                throw new LocalizedException(
                    __('Processors must implement ShippingProcessorInterface.')
                );
            }
        }
        $this->processors = $data;
    }

    /**
     * @param DataObject $shippingMethod
     * @param $quote
     */
    public function process(
        $quote,
        $object
    ) {
        foreach ($this->processors as $processor) {
            if ($processor->isApplicable($object->getCurrentMethod())) {
                return $processor->getPrice($quote,$object);
            }
        }
        $result['data'] = [];
        return $result;
    }
}
