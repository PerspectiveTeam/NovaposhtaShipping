<?php

namespace Perspective\NovaposhtaShipping\Api;

interface PreparedCitiesRepositoryInterface
{
    /**
     * Retrieve cities matching name.
     *
     * @param string $term | null
     * @return string | null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareCityArray($term = null);
}
