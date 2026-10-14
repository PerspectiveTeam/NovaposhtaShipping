<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class BoxWeight extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        foreach ($dataSource['data']['items'] ?? [] as $index => $item) {
            $dataSource['data']['items'][$index][$this->getData('name')] =
                $item['max_weight'] ?? $item['volumetric_weight'] ?? null;
        }
        return $dataSource;
    }
}
