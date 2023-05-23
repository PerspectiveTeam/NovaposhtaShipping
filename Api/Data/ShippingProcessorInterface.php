<?php

namespace Perspective\NovaposhtaShipping\Api\Data;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Responsible for processing address
 *
 * @api
 */
interface ShippingProcessorInterface
{
    /**
     * @param $quote
     * @param \Magento\Framework\DataObject $object
     * @return mixed
     */
    public function getPrice($quote, $object);

    /**
     * @param string $shippingCode
     * @return mixed
     */
    public function isApplicable($shippingCode);
}
