<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;

class AreaAndRegion
{

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi
     */
    private $novaposhtaApi;

    /**
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi $novaposhtaApi
     */
    public function __construct(\Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi $novaposhtaApi)
    {
        $this->novaposhtaApi = $novaposhtaApi;
    }

    /**
     * @param $cityRecipientString
     * @return mixed
     */
    public function getAreaAndRegionData($cityRecipientString)
    {
        $AreaAndRegionData = $this->novaposhtaApi->getApi()->request(
            'Address',
            'searchSettlements',
            [
                'calledMethod' => 'searchSettlements',
                'CityName' => $cityRecipientString,
                'Limit' => 100,
            ]
        );
        return $AreaAndRegionData;
    }
}
