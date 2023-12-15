<?php
/**
 * Box packing (3D bin packing, knapsack problem).
 *
 * @author Doug Wright
 */
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Model\Box;

use DVDoug\BoxPacker\Item;
use DVDoug\BoxPacker\Rotation;
use JsonSerializable;
use ReturnTypeWillChange;
use stdClass;

class TestItem implements Item, JsonSerializable
{
    private mixed $jsonSerializeOverride = null;

    /**
     * Test objects that recurse.
     *
     * @var stdClass
     */
    private readonly stdClass $a;

    /**
     * Test objects that recurse.
     *
     * @var stdClass
     */
    private readonly stdClass $b;

    /**
     * @var \DVDoug\BoxPacker\Rotation|int
     */
    private mixed $keepFlat;

    /**
     * TestItem constructor.
     */
    public function __construct(
       private readonly string $description,
       private readonly int $width,
       private readonly int $length,
       private readonly int $depth,
       private readonly int $weight,
       private readonly int $allowedRotation
    ) {
        $this->keepFlat = $allowedRotation;

        $this->a = new stdClass();
        $this->b = new stdClass();

        $this->a->b = $this->b;
        $this->b->a = $this->a;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getKeepFlat(): bool
    {
        return $this->keepFlat;
    }
    public function getAllowedRotation(): Rotation
    {
        return match ($this->allowedRotation) {
            Rotation::Never => Rotation::Never,
            Rotation::KeepFlat => Rotation::KeepFlat,
            Rotation::BestFit => Rotation::BestFit,
            default => Rotation::KeepFlat,
        };
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()/* : mixed */
    {
        if (isset($this->jsonSerializeOverride)) {
            return $this->jsonSerializeOverride;
        }

        return [
            'description' => $this->description,
            'width' => $this->width,
            'length' => $this->length,
            'depth' => $this->depth,
            'weight' => $this->weight,
            'keepFlat' => $this->keepFlat,
            'allowedRotation' => $this->allowedRotation,
        ];
    }

    public function setJsonSerializeOverride(mixed $override): void
    {
        $this->jsonSerializeOverride = $override;
    }
}
