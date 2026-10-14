<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Helper;

use DVDoug\BoxPacker\BoxListFactory;
use DVDoug\BoxPacker\ItemListFactory;
use DVDoug\BoxPacker\PackedBox;
use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\PackerFactory;
use Perspective\NovaposhtaShipping\Service\AvailableBoxes;
use Perspective\NovaposhtaShipping\Model\Box\LimitedSupplyTestBoxFactory;
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
     * @var ItemListFactory
     */
    private ItemListFactory $itemListFactory;

    /**
     * @var BoxListFactory
     */
    private BoxListFactory $boxListFactory;

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

    private LimitedSupplyTestBoxFactory $limitedSupplyBoxFactory;

    private array $boxVisualisationLinksArray;

    public function __construct(
        PackerFactory $packerFactory,
        TestItemFactory $ItemFactory,
        ProductRepositoryInterface $productRepositoryInterface,
        LimitedSupplyTestBoxFactory $limitedSupplyBoxFactory,
        private AvailableBoxes $availableBoxes,
        ItemListFactory $itemListFactory,
        BoxListFactory $boxListFactory
    ) {
        $this->packerFactory = $packerFactory;
        $this->itemFactory = $ItemFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->limitedSupplyBoxFactory = $limitedSupplyBoxFactory;
        $this->itemListFactory = $itemListFactory;
        $this->boxListFactory = $boxListFactory;
    }

    /**
     * @param array $products
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function calcSeats(array $products): array
    {
        $finalOptionSeats = [];
        $this->packer = null;
        $this->availableBoxes->setAvailableBoxes($this->getPacker());

        $widthProduct = $lengthProduct = $heightProduct = 0.00;

        /**
         * @var int $productIdx
         * @var QuoteItem|OrderItem $productVal
         */
        foreach ($products as $productIdx => $productVal) {
            $productModel = $this->productRepositoryInterface->getById($productVal->getProduct()->getId());
            $widthProduct = (int)ceil($productModel->getProductWidth() / 10);
            $lengthProduct = (int)ceil($productModel->getProductLength() / 10);
            $heightProduct = (int)ceil($productModel->getProductHeight() / 10);
            $weightProduct = (int)ceil($productModel->getWeight() * 1000);
            $productValQty = $productVal instanceof OrderItem
                ? $productVal->getQtyToShip()
                : $productVal->getQty();
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
                'quantity' => $productValQty
            ]));

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
            $this->packer = $this->packerFactory->create([
                'items' => $this->itemListFactory->create(),
                'boxes' => $this->boxListFactory->create(),
            ]);
        }
        return $this->packer;

    }
}
