<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Source;

use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;

class SaleContact
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
        $valueInAdmin = $this->novaposhtaHelper->getStoreConfigByCode('novaposhtashipping', 'sale_sender');
        $options[] = ['value' => '', 'label' => __('Select default contact person')];
        if ($valueInAdmin) {
            $data = explode(',', $valueInAdmin);

            $apiKey = (string)$this->novaposhtaHelper->getStoreConfigByCode('novaposhtashipping', 'api_key');
            $cacheId = 'np_sale_counterparty_contact_person_' . hash('sha256', $apiKey . ':' . $data[0]);
            if ($cacheResult = $this->cache->load($cacheId)) {
                $response = unserialize($cacheResult);
            } else {
                $response = $this->novaposhtaHelper->getApi()->getCounterpartyContactPersons($data[0]);
                if (($response['success'] ?? false) === true) {
                    $this->cache->save(serialize($response), $cacheId);
                }
            }


            if (array_key_exists('success', $response ?? [])) {
                if ($response['success'] === true) {
                    foreach ($response['data'] as $counterpartyFromApiIndex => $counterpartyFromApiValue) {
                        $options[] =
                            [
                                'label' => $counterpartyFromApiValue['Description'],
                                'value' => $counterpartyFromApiValue['Ref']
                            ];
                    }
                }
                return $options;
            } else {
                return [['label' => __('Unable to load sender contacts. Check the API key and try again.'), 'value' => '']];
            }
        }
        return $options;
    }
}
