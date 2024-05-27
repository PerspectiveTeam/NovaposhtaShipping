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

            $cacheId = 'np_sale_counterparty_contact_person';
            if ($cacheResult = $this->cache->load($cacheId)) {
                $response = unserialize($cacheResult);
            } else {
                $response = $this->novaposhtaHelper->getApi()->getCounterpartyContactPersons($data[0]);
                if (!empty($response)) {
                    $this->cache->save(serialize($response), $cacheId);
                }
            }


            if (array_key_exists('success', $response)) {
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
                return ['label' => __('Firstly you need to specify sale sender'), 'value' => -1];
            }
        }
        return [['label' => __('Error occurs(sale sender)'), 'value' => -1]];
    }
}
