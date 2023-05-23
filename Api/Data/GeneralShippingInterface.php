<?php

namespace Perspective\NovaposhtaShipping\Api\Data;

interface GeneralShippingInterface
{
    /**
     * @return $this
     */
    public function loadAddressInfo($quoteId);
}
