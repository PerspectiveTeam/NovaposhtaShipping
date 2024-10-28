<?php

namespace Perspective\NovaposhtaShipping\Api\Data;

/**
 * Responsible for processing address
 *
 * @api
 */
interface OrderShippmentProcessorInterface
{
    /**
     * @param \Magento\Framework\DataObject $object
     * @return array
     */
    public function doInternetDocument($object);

    /**
     * @param \Magento\Framework\DataObject $object
     * @return mixed
     */
    public function isApplicable($object);
}
