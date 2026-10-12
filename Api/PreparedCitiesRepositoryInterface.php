<?php

namespace Perspective\NovaposhtaShipping\Api;

interface PreparedCitiesRepositoryInterface
{
    /**
     * Retrieve cities matching name, optionally filtered by area ref.
     *
     * @param string $term | null
     * @param string $areaRef | null
     * @return string | null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareCityArray($term = null, $areaRef = null);
}
