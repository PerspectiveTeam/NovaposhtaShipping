<?php

namespace Perspective\NovaposhtaShipping\Api;

interface SenderRepositoryInterface
{
    /**
     * @return array
     */
    public function getSenderCounterparty();

    /**
     * @return string| null
     */
    public function getSenderContactPerson();

    /**
     * @return string| null
     */
    public function getSenderContactPersonAddress();
}
