<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Helper;

use DVDoug\BoxPacker\BoxList;
use DVDoug\BoxPacker\BoxListFactory;
use DVDoug\BoxPacker\ItemList;
use DVDoug\BoxPacker\ItemListFactory;
use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\PackerFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Boxpacker;
use Perspective\NovaposhtaShipping\Model\Box\LimitedSupplyTestBoxFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestBox;
use Perspective\NovaposhtaShipping\Model\Box\TestItemFactory;
use Perspective\NovaposhtaShipping\Service\AvailableBoxes;
use PHPUnit\Framework\TestCase;

class BoxpackerTest extends TestCase
{
    public function testRepeatedCalculationsDoNotRetainPreviouslyEnabledBoxes(): void
    {
        $lists = [];
        $boxLists = $this->createStub(BoxListFactory::class);
        $boxLists->method('create')->willReturnCallback(function () use (&$lists) {
            $lists[] = new BoxList();
            return $lists[array_key_last($lists)];
        });
        $itemLists = $this->createStub(ItemListFactory::class);
        $itemLists->method('create')->willReturnCallback(fn () => new ItemList());
        $packers = $this->createStub(PackerFactory::class);
        $packers->method('create')->willReturnCallback(fn ($data) => new Packer(...$data));
        $enabled = true;
        $provider = $this->createStub(AvailableBoxes::class);
        $provider->method('setAvailableBoxes')->willReturnCallback(function ($packer) use (&$enabled) {
            if ($enabled) {
                $packer->addBox(new TestBox('enabled', 100, 100, 100, 0, 100, 100, 100, 1000));
            }
        });
        $helper = new Boxpacker($packers, $this->createStub(TestItemFactory::class),
            $this->createStub(ProductRepositoryInterface::class), $this->createStub(LimitedSupplyTestBoxFactory::class),
            $provider, $itemLists, $boxLists);
        self::assertSame([], $helper->calcSeats([]));
        $enabled = false;
        self::assertSame([], $helper->calcSeats([]));
        self::assertCount(2, $lists);
        self::assertCount(1, $lists[0]);
        self::assertCount(0, $lists[1]);
    }
}
