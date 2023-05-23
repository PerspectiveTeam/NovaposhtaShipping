<?php

namespace Perspective\NovaposhtaShipping\Api\Data\Adminhtml;

/**
 * Responsible for processing address
 *
 * @api
 */
interface ProcessorInterface
{
    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @return void
     */
    public function process($cart);

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @return bool
     */
    public function isApplicable($cart);
}
