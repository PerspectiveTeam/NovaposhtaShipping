<?php

namespace Perspective\NovaposhtaShipping\Model\Quote\Info\Session;

class QuoteObject
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $session;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        \Magento\Framework\App\State $state
    ) {
        if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $this->session = $backendQuoteSession;
        } else {
            $this->session = $checkoutSession;
        }
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Backend\Model\Session\Quote|\Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuote()
    {
        return $this->session->getQuote();
    }
}
