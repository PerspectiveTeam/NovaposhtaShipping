<?php

namespace Perspective\NovaposhtaShipping\Model\Quote\Info\Type;

use Magento\Framework\Exception\LocalizedException;
use Perspective\NovaposhtaShipping\Api\Data\ProcessorInterface;

/**
 * This class is supposed to be abstract but
 * due to bug with Virtual Types in Magento
 * it needs to stay normal. Otherwise virtual classes
 * aren't instantiated
 */
class AbstractChain
{
    /**
     * @var ProcessorInterface[]
     */
    protected $processors;

    /**
     * @param array<mixed> $data
     * @throws LocalizedException
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new LocalizedException(
                    __('Processors must implement ProcessorInterface.')
                );
            }
        }
        $this->processors = $data;
    }

    /**
     * @param $cartId
     * @param $addressInformation
     * @return void
     */
    public function process(
        $cartId,
        $addressInformation
    ) {
        foreach ($this->processors as $processor) {
            if ($processor->isApplicable($cartId, $addressInformation)) {
                $processor->process($cartId, $addressInformation);
            }
        }
    }
}
