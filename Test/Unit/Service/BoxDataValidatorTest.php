<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Service;

use Magento\Framework\Exception\LocalizedException;
use Perspective\NovaposhtaShipping\Service\BoxDataValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BoxDataValidatorTest extends TestCase
{
    private function data(): array
    {
        return ['description_ua' => ' Box ', 'length' => 200, 'width' => 100, 'height' => 50, 'max_weight' => 5];
    }

    public function testDefaultsAndOnlyEditableFieldsAreReturned(): void
    {
        $result = (new BoxDataValidator())->validate($this->data() + [
            'id' => 99, 'source' => 'standard', 'ref' => 'api-ref', 'is_active' => 0, 'last_seen_at' => 'now',
        ]);
        self::assertSame('Box', $result['description_ua']);
        self::assertSame(1, $result['boxpacker_enabled']);
        self::assertSame(0.0, $result['empty_weight']);
        self::assertNull($result['inner_width']);
        self::assertArrayNotHasKey('source', $result);
        self::assertArrayNotHasKey('ref', $result);
        self::assertArrayNotHasKey('id', $result);
        self::assertArrayNotHasKey('is_active', $result);
        self::assertArrayNotHasKey('last_seen_at', $result);
    }

    public function testOptionalFieldsAndDisabledState(): void
    {
        $result = (new BoxDataValidator())->validate($this->data() + [
            'inner_length' => '190.001', 'inner_width' => '', 'inner_height' => 49,
            'empty_weight' => '0.25', 'boxpacker_enabled' => '0',
        ]);
        self::assertSame(190.001, $result['inner_length']);
        self::assertNull($result['inner_width']);
        self::assertSame(0.25, $result['empty_weight']);
        self::assertSame(0, $result['boxpacker_enabled']);
        self::assertSame(0.0, (new BoxDataValidator())->validate($this->data() + ['empty_weight' => ''])['empty_weight']);
    }

    #[DataProvider('invalidData')]
    public function testRejectsInvalidValues(array $changes): void
    {
        $this->expectException(LocalizedException::class);
        (new BoxDataValidator())->validate(array_replace($this->data(), $changes));
    }

    public static function invalidData(): array
    {
        return [
            [['description_ua' => ' ']], [['description_ua' => []]], [['description_ua' => str_repeat('x', 256)]],
            [['length' => 0]], [['width' => -1]], [['height' => []]], [['length' => INF]],
            [['length' => 1000000000]], [['max_weight' => 'bad']], [['max_weight' => 0.0001]],
            [['inner_width' => 101]], [['inner_height' => -1]], [['empty_weight' => -1]],
            [['empty_weight' => 5]], [['empty_weight' => 6]], [['boxpacker_enabled' => 'yes']],
        ];
    }
}
