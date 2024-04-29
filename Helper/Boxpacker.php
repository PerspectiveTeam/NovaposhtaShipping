<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Helper;

use DVDoug\BoxPacker\PackedBox;
use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\PackerFactory;
use DVDoug\BoxPacker\Rotation;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\CollectionFactory;
use Perspective\NovaposhtaShipping\Model\Box\LimitedSupplyTestBoxFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestBoxFactory;
use Perspective\NovaposhtaShipping\Model\Box\TestItemFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Throwable;

class Boxpacker
{
    /**
     * Статическая константа, на которую происходит деление, диктуется Новой Почтой
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
     * @var \Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\CollectionFactory
     */
    private CollectionFactory $packageTypesResourceModelCollectionFactory;

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
        LimitedSupplyTestBoxFactory $limitedSupplyBoxFactory,
        CollectionFactory $packageTypesResourceModelCollectionFactory
    ) {
        $this->packerFactory = $packerFactory;
        $this->boxFactory = $BoxFactory;
        $this->itemFactory = $ItemFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->limitedSupplyBoxFactory = $limitedSupplyBoxFactory;
        $this->packageTypesResourceModelCollectionFactory = $packageTypesResourceModelCollectionFactory;
    }

    /**
     * @param array $products
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function calcSeats(array $products): array
    {
        $finalOptionSeats = [];
        $this->getPacker();

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
            //коробка самого товара
            $this->getPacker()->addBox($this->limitedSupplyBoxFactory->create([
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
            /** $allowedRotation <---- Rotation::BestFit == 6*/
            $productSku = $productVal->getProduct()->getSku();
            $this->getPacker()->addItem($this->itemFactory->create([
                'description' => $productSku,
                'width' => $widthProduct,
                'length' => $lengthProduct,
                'depth' => $heightProduct,
                'weight' => $weightProduct,
                'allowedRotation' => 6, //6 - BestFit
            ]), (int) ceil($productValQty));
        }
        $this->boxVisualisationLinksArray = [];
        $packedBoxes = $this->getPacker()->pack();
        /** @var PackedBox $box */
        foreach ($packedBoxes as $box) {
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
                if (method_exists($box, 'generateVisualisationURL')) {
                    $this->boxVisualisationLinksArray[] = $box->generateVisualisationURL();
                }
            } catch (Throwable $e) {
                $this->boxVisualisationLinksArray[] = '';
            }
        }

        return $finalOptionSeats;
    }

    /**
     * Всі значення в граммах та міліметрах, крім `overallWeight` воно в кг
     *
     * @return void
     *@see overallWeight
     */
    public function setAvailableBoxes(): void
    {
        // Палети НП
        // від 30 до 500 кг - 408
        // змінено з 0 на 30 через причину того, що відбувається некоректна "упаковка" якщо товарів менш як 30 кг
        // (є інші "коробки", до 30 кг, які можна використати для цього випадку)
        if ($this->overallWeight >= 30 && $this->overallWeight < 500) {
            $this->addBoxesToPacker('80*120*170');
        }

        // від 500 до 750 кг - 612, 408
        if ($this->overallWeight >= 500 && $this->overallWeight <= 750) {
            $this->addBoxesToPacker('120*120*170');
        }

        /*
         * від 750 до 1000+ кг - 816, 612, 408
         * або всі доступные палети НП, на випадок якщо товар "битий" та неможливо отримати вагу
         */
        if ($this->overallWeight > 750 || !$this->overallWeight) {
//            if (!$this->overallWeight) {
//                 $this->packer->addBox(new TestBox('80*60*170', 800, 600, 1700, 0, 800, 600, 1700, 500000));
//            }

            $this->addBoxesToPacker('141*141*170');
        }
        //Дрібне пакування НП
        $packageCollection = $this->packageTypesResourceModelCollectionFactory->create();
        $packageCollection->addFieldToFilter('volumetric_weight', ['gt' => 0]);
        $packageCollection->addFieldToFilter('length', ['gt' => 0]);
        $packageCollection->addFieldToFilter('width', ['gt' => 0]);
        $packageCollection->addFieldToFilter('height', ['gt' => 0]);
        /** @var \Perspective\NovaposhtaCatalog\Api\Data\PackageInterface $package */
        foreach ($packageCollection as $package) {
            $this->getPacker()->addBox($this->boxFactory->create(
                [
                    'reference' => sprintf('%s - %s', $package->getDescriptionUa(), $package->getRef()),
                    'outerWidth' => ceil($package->getWidth() / 10),
                    'outerLength' => ceil($package->getLength() / 10),
                    'outerDepth' => ceil($package->getHeight() / 10),
                    'emptyWeight' => 0,
                    'innerWidth' => ceil($package->getWidth() / 10),
                    'innerLength' => ceil($package->getLength() / 10),
                    'innerDepth' => ceil($package->getHeight() / 10),
                    'maxWeight' => $package->getVolumetricWeight() * 1000 // НП повертає вагу в КГ - переводимо в грами
                ]
            ));
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
     * 1000000 - 1 тонна
     * @param string $reference
     */
    public function addBoxesToPacker(string $reference): void
    {
        switch ($reference) {
            case '141*141*170':
                $this->getPacker()->addBox($this->boxFactory->create(
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
                $this->getPacker()->addBox($this->boxFactory->create(
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
                $this->getPacker()->addBox($this->boxFactory->create(
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

    /**
     * @return  \DVDoug\BoxPacker\Packer
     */
    protected function getPacker()
    {
        if (!$this->packer) {
            $this->packer = $this->packerFactory->create();
        }
        return $this->packer;

    }
}
