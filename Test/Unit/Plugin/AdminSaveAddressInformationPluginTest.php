<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Controller\Adminhtml\Order\Create\Save;
use Perspective\NovaposhtaShipping\Model\Adminhtml\Quote\Info\Type\AbstractChain;
use Perspective\NovaposhtaShipping\Plugin\Controller\Adminhtml\SaveAddressInformationPlugin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminSaveAddressInformationPluginTest extends TestCase
{
    public function testStandardOrdersBypassNovaposhtaPersistence(): void
    {
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->expects(self::never())->method('get');
        $processor = $this->createMock(AbstractChain::class);
        $processor->expects(self::never())->method('process');
        $plugin = $this->plugin(['shipping_method' => 'flatrate_flatrate'], $repository, $processor);
        $result = $this->createStub(ResultInterface::class);
        self::assertSame($result, $plugin->aroundExecute($this->createStub(Save::class), fn() => $result));
    }

    #[DataProvider('billingFlags')]
    public function testRecipientMetadataUsesTheEffectiveShippingAddress(bool $sameAsBilling, string $expected): void
    {
        $address = (new \ReflectionClass(Address::class))->newInstanceWithoutConstructor();
        $address->setData('same_as_billing', $sameAsBilling);
        $quote = $this->createStub(Quote::class);
        $quote->method('getId')->willReturn(42);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('setShippingAddress')->willReturnSelf();
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->expects(self::once())->method('get')->with(42)->willReturn($quote);
        $processor = $this->createMock(AbstractChain::class);
        $processor->expects(self::once())->method('process')->with($quote);
        $data = ['shipping_method' => 'novaposhtashipping_c2c'];
        foreach (['billing', 'shipping'] as $type) {
            $data[$type . '_address'] = [
                'novaposhta_city' => $type . '-city',
                'novaposhta_warehouse' => $type . '-warehouse',
                'novaposhta_street' => $type . '-street-ref',
                'street' => [$type . '-street-label', '10', '1']
            ];
        }
        $plugin = $this->plugin($data, $repository, $processor);
        $plugin->quote = $quote;
        $result = $this->createStub(ResultInterface::class);
        self::assertSame($result, $plugin->aroundExecute($this->createStub(Save::class), fn() => $result));
        self::assertSame($expected . '-city', $address->getData('perspective_novaposhta_shipping_city'));
        self::assertSame($expected . '-street-ref', $address->getData('perspective_novaposhta_shipping_street'));
        self::assertSame('c2c', $address->getData('shipping_method_code'));
    }

    public static function billingFlags(): array
    {
        return [[true, 'billing'], [false, 'shipping']];
    }

    private function plugin(array $data, CartRepositoryInterface $repository, AbstractChain $processor): AdminSavePluginProbe
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturn($data);
        $plugin = new AdminSavePluginProbe();
        $plugin->request = $request;
        foreach (['cartRepository' => $repository, 'cartInfoPreserver' => $processor] as $name => $value) {
            (new \ReflectionProperty(SaveAddressInformationPlugin::class, $name))->setValue($plugin, $value);
        }
        return $plugin;
    }
}

class AdminSavePluginProbe extends SaveAddressInformationPlugin
{
    public RequestInterface $request;
    public Quote $quote;

    public function __construct()
    {
    }

    public function getRequest()
    {
        return $this->request;
    }

    protected function _getQuote()
    {
        return $this->quote;
    }
}
