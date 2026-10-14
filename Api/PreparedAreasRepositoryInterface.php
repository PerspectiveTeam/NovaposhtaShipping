<?php

namespace Perspective\NovaposhtaShipping\Api;

interface PreparedAreasRepositoryInterface
{
    const LOCALE_RU = 'ru_RU';

    /**
     * Retrieve areas matching name.
     *
     * @param string $term | null
     * @return string | null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareAreaArray($term = null);
}
