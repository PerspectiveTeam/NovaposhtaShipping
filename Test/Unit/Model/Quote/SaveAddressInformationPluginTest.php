<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Model\Quote;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Perspective\NovaposhtaShipping\Model\Quote\Info\Type\AbstractChain;
use Perspective\NovaposhtaShipping\Model\Quote\SaveAddressInformationPlugin;
use PHPUnit\Framework\TestCase;

class SaveAddressInformationPluginTest extends TestCase
{
    public function testOtherCarriersDoNotRunNovaposhtaProcessors(): void
    {
        $information = $this->createStub(ShippingInformationInterface::class);
        $information->method('getShippingCarrierCode')->willReturn('flatrate');
        $processor = $this->createMock(AbstractChain::class);
        $processor->expects(self::never())->method('process');
        (new SaveAddressInformationPlugin($processor))->beforeSaveAddressInformation(
            $this->createStub(ShippingInformationManagement::class),
            42,
            $information
        );
    }

    public function testNovaposhtaRunsItsProcessors(): void
    {
        $information = $this->createStub(ShippingInformationInterface::class);
        $information->method('getShippingCarrierCode')->willReturn('novaposhtashipping');
        $processor = $this->createMock(AbstractChain::class);
        $processor->expects(self::once())->method('process')->with(42, $information);
        (new SaveAddressInformationPlugin($processor))->beforeSaveAddressInformation(
            $this->createStub(ShippingInformationManagement::class),
            42,
            $information
        );
    }
}
