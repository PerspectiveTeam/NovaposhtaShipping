<?php

namespace Perspective\NovaposhtaShipping\Model\Quote;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Perspective\NovaposhtaShipping\Model\Quote\Info\Type\AbstractChain;

/**
 * Class SaveAddressInformationPlugin
 * Intercept data from checkout
 */
class SaveAddressInformationPlugin
{
    /**
     * @var \Perspective\NovaposhtaShipping\Model\Quote\Info\Type\AbstractChain
     */
    private $shippingCartProcessor;

    /**
     * SaveAddressInformationPlugin constructor.
     * @param \Perspective\NovaposhtaShipping\Model\Quote\Info\Type\AbstractChain $shippingCartProcessor
     */
    public function __construct(
        AbstractChain $shippingCartProcessor
    ) {
        $this->shippingCartProcessor = $shippingCartProcessor;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $this->shippingCartProcessor->process($cartId, $addressInformation);
    }
}
