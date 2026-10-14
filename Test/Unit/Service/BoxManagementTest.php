<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Perspective\NovaposhtaCatalog\Model\Package\Package;
use Perspective\NovaposhtaCatalog\Model\Package\PackageFactory;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package as Resource;
use Perspective\NovaposhtaCatalog\Model\Update\DirectorySnapshot;
use Perspective\NovaposhtaShipping\Service\BoxDataValidator;
use Perspective\NovaposhtaShipping\Service\BoxManagement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BoxManagementTest extends TestCase
{
    private array $stored = [];

    private function service(): BoxManagement
    {
        $factory = $this->createStub(PackageFactory::class);
        $factory->method('create')->willReturnCallback(fn () => (new \ReflectionClass(Package::class))->newInstanceWithoutConstructor());
        $resource = $this->createStub(Resource::class);
        $resource->method('load')->willReturnCallback(function ($model, $id) use ($resource) {
            $model->setData($this->stored[$id] ?? []);
            return $resource;
        });
        $resource->method('save')->willReturnCallback(function ($model) use ($resource) {
            if (!$model->getId()) {
                $model->setId(count($this->stored) + 1);
            }
            $this->stored[$model->getId()] = $model->getData();
            return $resource;
        });
        $resource->method('delete')->willReturnCallback(function ($model) use ($resource) {
            unset($this->stored[$model->getId()]);
            return $resource;
        });
        $snapshot = $this->createStub(DirectorySnapshot::class);
        $snapshot->method('withLock')->willReturnCallback(function ($lock, $callback) {
            self::assertSame(BoxManagement::LOCK_NAME, $lock);
            return $callback();
        });
        return new BoxManagement($factory, $resource, $snapshot, new BoxDataValidator());
    }

    private function data(): array
    {
        return ['description_ua' => 'Box', 'length' => 200, 'width' => 100, 'height' => 50, 'max_weight' => 5];
    }

    #[DataProvider('protectedSources')]
    public function testProtectedBoxesRejectEditingAndDeletion(string $source, string $operation): void
    {
        $this->stored[1] = $this->data() + ['id' => 1, 'source' => $source, 'ref' => 'protected'];
        $service = $this->service();
        $before = $this->stored;
        try {
            if ($operation === 'edit') {
                $service->save($this->data() + ['source' => 'custom'], 1);
            } else {
                $service->delete(1);
            }
            self::fail('Protected operation succeeded');
        } catch (LocalizedException $error) {
            self::assertSame($before, $this->stored);
        }
    }

    public static function protectedSources(): array
    {
        return [['api', 'edit'], ['standard', 'edit'], ['api', 'delete'], ['standard', 'delete']];
    }

    #[DataProvider('origins')]
    public function testCopiesHaveIndependentIdentityAndNoSyncMetadata(string $source): void
    {
        $original = $this->data() + ['id' => 1, 'source' => $source, 'ref' => 'original',
            'boxpacker_enabled' => 0, 'is_active' => 0, 'last_seen_at' => '2026-01-01 00:00:00', 'missing_successful_syncs' => 4];
        $this->stored[1] = $original;
        $service = $this->service();
        $copy = $service->duplicate(1, ['description_ua' => 'Copy', 'id' => 1, 'ref' => 'original', 'source' => 'api']);
        self::assertSame('custom', $copy->getSource());
        self::assertSame(0, $copy->getBoxpackerEnabled());
        self::assertNotSame('original', $copy->getRef());
        self::assertSame(1, $copy->getData('is_active'));
        self::assertNull($copy->getData('last_seen_at'));
        self::assertNull($copy->getData('missing_successful_syncs'));
        self::assertSame($original, $this->stored[1]);
        $service->save(array_replace($this->data(), ['width' => 150]), (int)$copy->getId());
        self::assertSame(150.0, $service->getById((int)$copy->getId())->getWidth());
        $service->delete((int)$copy->getId());
        self::assertSame([1 => $original], $this->stored);
    }

    public static function origins(): array
    {
        return [['api'], ['standard'], ['custom']];
    }

    public function testTogglePreservesApiAvailabilityAndParameters(): void
    {
        $original = $this->data() + ['id' => 1, 'source' => 'api', 'ref' => 'api', 'is_active' => 0, 'boxpacker_enabled' => 1];
        $this->stored[1] = $original;
        $service = $this->service();
        $service->setEnabled(1, false);
        self::assertSame(array_replace($original, ['boxpacker_enabled' => 0]), $this->stored[1]);
        $service->setEnabled(1, true);
        self::assertSame($original, $this->stored[1]);
    }

    public function testMissingIdDoesNotBecomeANewBox(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->service()->save($this->data(), 999);
    }
}
