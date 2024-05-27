<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Source;

use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;

class Sale
{
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;

    /**
     * @var \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache
     */
    private OperationsCache $cache;

    public function __construct(
        NovaposhtaHelper $novaposhtaHelper,
        OperationsCache $cache,
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->cache = $cache;
    }


    public function toOptionArray($isMultiselect = false)
    {
        $cacheId = 'np_sale_counterparties';
        if ($cacheResult = $this->cache->load($cacheId)) {
            $response = unserialize($cacheResult);
        } else {
            $response = $this->novaposhtaHelper->getApi()->getCounterparties('Sender');
            if (!empty($response)) {
                $this->cache->save(serialize($response), $cacheId);
            }
        }
        if (array_key_exists('success', $response)) {
            if ($response['success'] === true) {
                foreach ($response['data'] as $counterpartyFromApiIndex => $counterpartyFromApiValue) {
                    $options[] =
                        [
                            'label' => $counterpartyFromApiValue['Description'] . ' ' . $counterpartyFromApiValue['CityDescription'],
                            'value' => $counterpartyFromApiValue['Ref']
                        ];
                }
                return $options;
            } else {
                return ['label' => __('Firstly you need to specify API key'), 'value' => -1];
            }
        }
        return [];
    }
}
