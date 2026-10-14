<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Service;

use DVDoug\BoxPacker\BoxList;
use DVDoug\BoxPacker\Packer;
use Perspective\NovaposhtaCatalog\Model\Package\Package;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\Collection;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\CollectionFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestBox;
use Perspective\NovaposhtaShipping\Model\Box\TestBoxFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestItem;
use Perspective\NovaposhtaShipping\Service\AvailableBoxes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AvailableBoxesTest extends TestCase
{
    private function service(array $rows): AvailableBoxes
    {
        $models = [];
        foreach ($rows as $row) {
            $model = (new \ReflectionClass(Package::class))->newInstanceWithoutConstructor();
            $model->setData($row);
            $models[] = $model;
        }
        $collection = $this->createStub(Collection::class);
        $filters = [];
        $collection->method('addFieldToFilter')->willReturnCallback(function ($field, $value) use (&$filters, $collection) {
            $filters[$field] = $value;
            return $collection;
        });
        $collection->method('getIterator')->willReturnCallback(function () use (&$filters, $models) {
            self::assertSame(['is_active' => 1, 'boxpacker_enabled' => 1, 'length' => ['gt' => 0],
                'width' => ['gt' => 0], 'height' => ['gt' => 0]], $filters);
            return new \ArrayIterator($models);
        });
        $collections = $this->createStub(CollectionFactory::class);
        $collections->method('create')->willReturn($collection);
        $boxes = $this->createStub(TestBoxFactory::class);
        $boxes->method('create')->willReturnCallback(fn ($data) => new TestBox(...$data));
        return new AvailableBoxes($collections, $boxes);
    }

    private function row(string $ref, int $dimension, float $maxWeight): array
    {
        return ['ref' => $ref, 'description_ua' => $ref, 'length' => $dimension, 'width' => $dimension,
            'height' => $dimension, 'max_weight' => $maxWeight];
    }

    #[DataProvider('weights')]
    public function testAllPalletsAreAddedRegardlessOfItemWeight(int $weight): void
    {
        $boxes = new BoxList();
        $packer = new Packer(boxes: $boxes);
        $this->service([
            $this->row('pallet-500', 1200, 500), $this->row('pallet-750', 1200, 750),
            $this->row('pallet-1000', 1410, 1000),
        ])->setAvailableBoxes($packer);
        self::assertCount(3, $boxes);
        $packer->addItem(new TestItem('item', 10, 10, 10, $weight, 6));
        self::assertCount(1, $packer->pack());
    }

    public static function weights(): array
    {
        return [[0], [29000], [30000], [499999], [500000], [750000], [750001]];
    }

    public function testDimensionsAndTareAreConvertedAndPackerChoosesAFeasibleBox(): void
    {
        $boxes = new BoxList();
        $packer = new Packer(boxes: $boxes);
        $this->service([
            $this->row('too-small', 20, 10),
            $this->row('too-light', 300, 0.5),
            $this->row('fits', 300, 2) + ['inner_width' => 250, 'empty_weight' => 0.1],
        ])->setAvailableBoxes($packer);
        $packer->addItem(new TestItem('item', 20, 20, 20, 1000, 6));
        $packed = $packer->pack();
        self::assertCount(1, $packed);
        foreach ($packed as $box) {
            self::assertSame('fits - fits', $box->box->getReference());
            self::assertSame(25, $box->box->getInnerWidth());
            self::assertSame(1100, $box->getWeight());
        }
    }

    public function testApiWeightFallbackAndZeroWeightPackagingArePassedToPacker(): void
    {
        $row = $this->row('api', 100, 1);
        unset($row['max_weight']);
        $row['volumetric_weight'] = 0.5;
        $boxes = new BoxList();
        $this->service([$row, $this->row('zero', 100, 0)])->setAvailableBoxes(new Packer(boxes: $boxes));
        $weights = [];
        foreach ($boxes as $box) {
            $weights[$box->getReference()] = $box->getMaxWeight();
        }
        self::assertSame(500, $weights['api - api']);
        self::assertSame(0, $weights['zero - zero']);
    }
}
