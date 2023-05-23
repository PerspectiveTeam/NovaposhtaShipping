<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;

class ShippingRepository
{
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private QuoteIdMaskFactory $quoteIdMaskFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask
     */
    private QuoteIdMaskResource $quoteIdMaskResource;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteIdMaskResource $quoteIdMaskResource
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    public function resolveCartId($cartId)
    {
        /** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $this->quoteIdMaskResource->load($quoteIdMask, $cartId, 'masked_id');
        if ($quoteIdMask->getId()) {
            return $quoteIdMask->getQuoteId();
        } else {
            return $cartId;
        }
    }
}
