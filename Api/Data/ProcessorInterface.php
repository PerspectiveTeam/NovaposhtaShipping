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
interface ProcessorInterface
{
    /**
     * @param int|string $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return void
     */
    public function process($cartId, $addressInformation);

    /**
     * @param int|string $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return bool
     */
    public function isApplicable($cartId, $addressInformation);
}
