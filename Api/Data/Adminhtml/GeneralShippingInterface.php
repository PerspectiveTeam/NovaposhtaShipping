<?php

namespace Perspective\NovaposhtaShipping\Api\Data\Adminhtml;

interface GeneralShippingInterface
{
    /**
     * @return $this
     */
    public function loadAddressInfo($quoteId);

    /**
     * @param bool $isInfo
     * @return void
     */
    public function setIsInfo($isInfo);

    /**
     * @return bool
     */
    public function getIsInfo();
}
