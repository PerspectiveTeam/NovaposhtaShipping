<?php

namespace Perspective\NovaposhtaShipping\Api;

interface PreparedWarehousesRepositoryInterface
{
    /**
     * Retrieve warehouse by city ref.
     *
     * @param string $term | null
     * @return string | null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareWarehouseArray($term = null);
    /**
     * Retrieve warehouse by city ref.
     *
     * @param string $term | null
     * @param string $cityRef
     * @return string | null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function filteredWarehouseArrayByCityRefAndTerm($cityRef, $term = null);
}
