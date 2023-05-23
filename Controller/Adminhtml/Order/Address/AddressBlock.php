<?php

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Order\Address;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

/**
 * Class AddressBlock
 * Invokes render of form with select
 * because even if they not need in the order they will hang page
 */
class AddressBlock extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $layoutFactory;

    /**
     * AddressBlock constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $html = $this->getNovaposhtaAddressForm();
        $this->getResponse()->setBody($html);
    }

    /**
     * @return mixed
     */
    public function getNovaposhtaAddressForm()
    {
        return $this->_view->getLayout()->
        createBlock(\Magento\Backend\Block\Template::class)
            ->setTemplate('Perspective_NovaposhtaShipping::order/address/novaposhtaOnAdminChangeAddress.phtml')
            ->toHtml();
    }
}
