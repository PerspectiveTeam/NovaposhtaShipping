<?php

namespace Perspective\NovaposhtaShipping\Plugin\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Catalog\Helper\Product;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\Adminhtml\Order\Create\Save;
use Perspective\NovaposhtaShipping\Model\Adminhtml\Quote\Info\Type\AbstractChain;
use Magento\Quote\Api\CartRepositoryInterface;

class SaveAddressInformationPlugin extends Save
{

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Quote\Info\Type\AbstractChain
     */
    private $cartInfoPreserver;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Perspective\NovaposhtaShipping\Model\Adminhtml\Quote\Info\Type\AbstractChain $cartInfoPreserver
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Action\Context $context,
        Product $productHelper,
        Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        AbstractChain $cartInfoPreserver,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($context, $productHelper, $escaper, $resultPageFactory, $resultForwardFactory);
        $this->cartInfoPreserver = $cartInfoPreserver;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Magento\Sales\Controller\Adminhtml\Order\Create\Save $subject
     * @param callable $proceed
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundExecute(Save $subject, callable $proceed): ResultInterface
    {
        $cityRef = $this->getRequest()->getParam('order')['billing_address']['novaposhta_city']
            ?? $this->getRequest()->getParam('order')['shipping_address']['novaposhta_city']
            ?? null;
        $warehouseRef = $this->getRequest()->getParam('order')['billing_address']['novaposhta_warehouse']
            ?? $this->getRequest()->getParam('order')['shipping_address']['novaposhta_warehouse']
            ?? null;
        $street = $this->getRequest()->getParam('order')['billing_address']['street'][0]
            ?? $this->getRequest()->getParam('order')['shipping_address']['street'][0]
            ?? null;
        $buildingNum = $this->getRequest()->getParam('order')['billing_address']['street'][1]
            ?? $this->getRequest()->getParam('order')['shipping_address']['street'][1]
            ?? null;
        $flatNum = $this->getRequest()->getParam('order')['billing_address']['street'][2]
            ?? $this->getRequest()->getParam('order')['shipping_address']['street'][2]
            ?? null;
        $shippingMethod = $this->getRequest()->getParam('order')['shipping_method'];
        $cartId = (int)$this->_getQuote()->getId();
        $result = $proceed();
        $cart = $this->cartRepository->get($cartId);
        $this->processShippingCode($cart, $shippingMethod);
        $this->processShippingCity($cart, $cityRef);
        $this->processShippingWarehouse($cart, $warehouseRef);
        $this->processShippingStreet($cart, $street);
        $this->processShippingBuilding($cart, $buildingNum);
        $this->processShippingFlat($cart, $flatNum);
        $this->cartInfoPreserver->process($cart);
        return $result;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param $shippingMethod
     * @return void
     */
    protected function processShippingCode(\Magento\Quote\Api\Data\CartInterface $cart, $shippingMethod): void
    {
        $cart->getShippingAddress()->setData('shipping_method_code', str_replace('novaposhtashipping_', '', $shippingMethod));
        $cart->setShippingAddress($cart->getShippingAddress());
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param $data
     * @return void
     */
    protected function processShippingCity(\Magento\Quote\Api\Data\CartInterface $cart, $data): void
    {
        $cart->getShippingAddress()->setData('perspective_novaposhta_shipping_city', $data);
        $cart->setShippingAddress($cart->getShippingAddress());
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param $data
     * @return void
     */
    protected function processShippingWarehouse(\Magento\Quote\Api\Data\CartInterface $cart, $data): void
    {
        $cart->getShippingAddress()->setData('perspective_novaposhta_shipping_warehouse', $data);
        $cart->setShippingAddress($cart->getShippingAddress());
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param $data
     * @return void
     */
    protected function processShippingStreet(\Magento\Quote\Api\Data\CartInterface $cart, $data): void
    {
        $cart->getShippingAddress()->setData('perspective_novaposhta_shipping_street', $data);
        $cart->setShippingAddress($cart->getShippingAddress());
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param $data
     * @return void
     */
    protected function processShippingBuilding(\Magento\Quote\Api\Data\CartInterface $cart, $data): void
    {
        $cart->getShippingAddress()->setData('perspective_novaposhta_shipping_building', $data);
        $cart->setShippingAddress($cart->getShippingAddress());
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param $data
     * @return void
     */
    protected function processShippingFlat(\Magento\Quote\Api\Data\CartInterface $cart, $data): void
    {
        $cart->getShippingAddress()->setData('perspective_novaposhta_shipping_flat', $data);
        $cart->setShippingAddress($cart->getShippingAddress());
    }
}
