<?php

namespace Perspective\NovaposhtaShipping\Api;

interface PreparedStreetsRepositoryInterface
{
    /**
     * Retrieve warehouse by city ref.
     *
     * @param string $term | null
     * @return string | null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareStreetsArray($term = null);
    /**
     * Retrieve warehouse by city ref.
     *
     * @param string $term | null
     * @param string $cityRef
     * @return string | null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function filteredStreetsArrayByCityRefAndTerm($cityRef, $term = null);
}
