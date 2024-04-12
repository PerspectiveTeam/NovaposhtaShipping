<?php

namespace Perspective\NovaposhtaShipping\Api;

interface SenderRepositoryInterface
{
    /**
     * @param string $term
     * @return string| null
     */
    public function getSenderCities($term = null);
    /**
     * @param string $term
     * @return string| null
     */
    public function getSenderContactPerson($term = null);
    /**
     * @param string $term
     * @return string| null
     */
    public function getSenderContactPersonAddress($term = null);
}
