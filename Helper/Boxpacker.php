<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Helper;

use DVDoug\BoxPacker\PackedBox;
use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\PackerFactory;
use DVDoug\BoxPacker\Rotation;
use Perspective\NovaposhtaShipping\Model\Box\LimitedSupplyTestBoxFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestBoxFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestItemFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class Boxpacker
{
    /**
     * Статическая константа, на которую происходит деление, диктуеться Новой Почтой
     */
    protected const VOLUMETRIC_VOLUME_DIVISOR = 4000;

    /**
     * @var PackerFactory $packerFactory
     */
    private $packerFactory;

    /**
     * @var TestBoxFactory $boxFactory
     */
    private $boxFactory;

    /**
     * @var TestItemFactory $itemFactory
     */
    private $itemFactory;

    /**
     * @var Packer|null $packer
     */
    private $packer;

    /**
     * @var ProductRepositoryInterface $productRepositoryInterface
     */
    private $productRepositoryInterface;

    /**
     * @var float $overallWeight
     */
    private $overallWeight = 0.00;

    private LimitedSupplyTestBoxFactory $limitedSupplyBoxFactory;

    private array $boxVisualisationLinksArray;

    /**
     * Boxpacker constructor.
     *
     * @param PackerFactory $packerFactory
     * @param TestBoxFactory $BoxFactory
     * @param TestItemFactory $ItemFactory
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Perspective\NovaposhtaShipping\Model\Box\LimitedSupplyTestBoxFactory $limitedSupplyBoxFactory
     */
    public function __construct(
        PackerFactory $packerFactory,
        TestBoxFactory $BoxFactory,
        TestItemFactory $ItemFactory,
        ProductRepositoryInterface $productRepositoryInterface,
        LimitedSupplyTestBoxFactory $limitedSupplyBoxFactory
    ) {
        $this->packerFactory = $packerFactory;
        $this->boxFactory = $BoxFactory;
        $this->itemFactory = $ItemFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->limitedSupplyBoxFactory = $limitedSupplyBoxFactory;
    }

    /**
     * @param array $products
     * @return array
     */
    public function calcSeats(array $products): array
    {
        $finalOptionSeats = [];
        $this->packer = $this->packerFactory->create();

        /**
         * @var int $productIdx
         * @var QuoteItem|OrderItem $productVal
         */
        foreach ($products as $productIdx => $productVal) {
            $this->overallWeight += ($productVal->getWeight() * $productVal->getQty());
        }

        $this->setAvailableBoxes();

        $widthProduct = $lengthProduct = $heightProduct = 0.00;

        /**
         * @var int $productIdx
         * @var QuoteItem|OrderItem $productVal
         */
        foreach ($products as $productIdx => $productVal) {
            $productModel = $this->productRepositoryInterface->get($productVal->getProduct()->getSku());
            $widthProduct = (int)ceil($productModel->getProductWidth() / 10);
            $lengthProduct = (int)ceil($productModel->getProductLength() / 10);
            $heightProduct = (int)ceil($productModel->getProductHeight() / 10);
            $weightProduct = (int)ceil($productModel->getWeight() * 1000);
            $this->packer->addBox($this->limitedSupplyBoxFactory->create([
                'reference' => 'product_size_box_' . uniqid(),
                'outerWidth' => $widthProduct,
                'outerLength' => $lengthProduct,
                'outerDepth' => $heightProduct,
                'emptyWeight' => 0,
                'innerWidth' => $widthProduct,
                'innerLength' => $lengthProduct,
                'innerDepth' => $heightProduct,
                'maxWeight' => $weightProduct,
                'quantity' => $productVal->getQty()
            ]));

            $productValQty = $productVal instanceof OrderItem
                ? $productVal->getQtyToShip()
                : $productVal->getQty();

            /** $allowedRotation <---- Rotation::KeepFlat == 2*/
            $this->packer->addItem($this->itemFactory->create([
                'description' => $productIdx,
                'width' => $widthProduct,
                'length' => $lengthProduct,
                'depth' => $heightProduct,
                'weight' => $weightProduct,
                'allowedRotation' => 2,
            ]), (int) ceil($productValQty));
        }
        $this->boxVisualisationLinksArray = [];
        /** @var PackedBox $box */
        foreach ($this->packer->pack() as $box) {
            $boxWidth = $box->getUsedWidth() / 10;
            $boxHeight = $box->getUsedDepth() / 10;
            $boxLength = $box->getUsedLength() / 10;
            $boxWeight = $box->getWeight() / 1000;

            if ($boxWeight < NovaposhtaHelper::PALLETE_THRESHOLD) {
                $width = $this->isDimensionGtZero($boxWidth) ? $boxWidth : $widthProduct;
                $height = $this->isDimensionGtZero($boxHeight) ? $boxHeight : $heightProduct;
                $length = $this->isDimensionGtZero($boxLength) ? $boxLength : $lengthProduct;
                $weight = $boxWeight;
            } else {
                $width = $boxWidth;
                $height = $boxHeight;
                $length = $boxLength;
                $weight = $box->getWeight() / 1000;
            }

            $finalOptionSeats[] = [
                'volumetricVolume' => $this->calculateVolumetricVolume($width, $height, $length),
                'volumetricWidth' => $width,
                'volumetricHeight' => $height,
                'volumetricLength' => $length,
                'weight' => $weight,
            ];
            try {
                $this->boxVisualisationLinksArray[] = $box->generateVisualisationURL();
            } catch (\Exception $e) {
                $this->boxVisualisationLinksArray[] = '';
            }
        }

        return $finalOptionSeats;
    }

    /**
     * Все значения в граммах и миллиметрах, кроме `overallWeight` оно в кг
     * @see overallWeight
     * @return void
     */
    protected function setAvailableBoxes(): void
    {
        // от 0 до 500 кг - 408
        if ($this->overallWeight >= 0 && $this->overallWeight < 500) {
            $this->addBoxesToPacker('80*120*170');
        }

        // от 500 до 750 кг - 612, 408
        if ($this->overallWeight >= 500 && $this->overallWeight <= 750) {
            $this->addBoxesToPacker('120*120*170');
        }

        /*
         * от 750 до 1000+ кг - 816, 612, 408
         * или все доступные палеты НП, на случай если товар битый и невозможно получить вес
         */
        if ($this->overallWeight > 750 || !$this->overallWeight) {
//            if (!$this->overallWeight) {
//                 $this->packer->addBox(new TestBox('80*60*170', 800, 600, 1700, 0, 800, 600, 1700, 500000));
//            }

            $this->addBoxesToPacker('141*141*170');
        }
    }

    /**
     * @param float $width
     * @param float $height
     * @param float $length
     * @return float
     */
    private function calculateVolumetricVolume(float $width, float $height, float $length): float
    {
        return ($width * $height * $length) / static::VOLUMETRIC_VOLUME_DIVISOR;
    }

    /**
     * @param string $reference
     */
    private function addBoxesToPacker(string $reference): void
    {
        switch ($reference) {
            case '141*141*170':
                $this->packer->addBox($this->boxFactory->create(
                    [
                        'reference' => '141*141*170',
                        'outerWidth' => 141,
                        'outerLength' => 141,
                        'outerDepth' => 170,
                        'emptyWeight' => 0,
                        'innerWidth' => 141,
                        'innerLength' => 141,
                        'innerDepth' => 170,
                        'maxWeight' => 1000000
                    ]
                ));
                $this->addBoxesToPacker('120*120*170');
                break;
            case '120*120*170':
                $this->packer->addBox($this->boxFactory->create(
                    [
                        'reference' => '120*120*170',
                        'outerWidth' => 120,
                        'outerLength' => 120,
                        'outerDepth' => 170,
                        'emptyWeight' => 0,
                        'innerWidth' => 120,
                        'innerLength' => 120,
                        'innerDepth' => 170,
                        'maxWeight' => 750000
                    ]
                ));
                $this->addBoxesToPacker('80*120*170');
                break;
            case '80*120*170':
                $this->packer->addBox($this->boxFactory->create(
                    [
                        'reference' => '80*120*170',
                        'outerWidth' => 80,
                        'outerLength' => 120,
                        'outerDepth' => 170,
                        'emptyWeight' => 0,
                        'innerWidth' => 80,
                        'innerLength' => 120,
                        'innerDepth' => 170,
                        'maxWeight' => 500000
                    ]
                ));
                break;
        }
    }

    /**
     * @param float $dimension
     * @return bool
     */
    private function isDimensionGtZero(float $dimension): bool
    {
        /*
         * В прошлом эта формула выглядела `$dimension / 10 !== 0`,
         * но по логике этой формулы значение может быть и меньше нуля чего нельзя допускать,
         * так как апи НП не отдаёт ошибку при обработке минусовых значений
         */
        return ($dimension / 10) > 0;
    }

    public function getBoxVisualisationLinksArray(): array
    {
        return $this->boxVisualisationLinksArray;
    }
}
