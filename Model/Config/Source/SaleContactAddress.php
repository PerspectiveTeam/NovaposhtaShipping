<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Source;

use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;

class SaleContactAddress
{
    public function __construct(
        private NovaposhtaHelper $novaposhtaHelper,
        private OperationsCache $cache
    ) {
    }

    public function toOptionArray($isMultiselect = false)
    {
        $options = [['value' => '', 'label' => __('Select default contact person address')]];
        $sender = explode(',', (string)$this->novaposhtaHelper->getStoreConfigByCode(
            'novaposhtashipping',
            'sale_sender'
        ))[0];
        $contact = (string)$this->novaposhtaHelper->getStoreConfigByCode(
            'novaposhtashipping',
            'sale_sender_contact'
        );
        $apiKey = (string)$this->novaposhtaHelper->getStoreConfigByCode('novaposhtashipping', 'api_key');
        if ($sender === '' || $apiKey === '') {
            return $options;
        }

        $cacheId = 'np_sender_addresses_' . hash('sha256', $apiKey . ':' . $sender);
        if ($cached = $this->cache->load($cacheId)) {
            $response = unserialize($cached);
        } else {
            $response = $this->novaposhtaHelper->getApi()->request(
                'ContactPersonGeneral',
                'getContactPersonsList',
                [
                    'ContactProperty' => 'Sender',
                    'CounterpartyRef' => $sender,
                    'getContactPersonAddress' => 1,
                    'Limit' => 200,
                    'Page' => 1
                ]
            );
            if (($response['success'] ?? false) === true) {
                $this->cache->save(serialize($response), $cacheId);
            }
        }

        if (($response['success'] ?? false) !== true) {
            return [['value' => '', 'label' => __('Unable to load sender addresses. Please try again.')]];
        }

        $seen = [];
        foreach ($response['data'] ?? [] as $person) {
            if ($contact !== '' && ($person['Ref'] ?? '') !== $contact) {
                continue;
            }
            foreach ($person['Addresses'] ?? [] as $type => $addresses) {
                foreach ($addresses as $address) {
                    $ref = $address['Ref'] ?? '';
                    if ($ref === '' || isset($seen[$ref])) {
                        continue;
                    }
                    if ($type === 'DoorsAddresses') {
                        $label = trim(implode(' ', [
                            $address['SettlementDescription'] ?? '',
                            $address['StreetsType'] ?? '',
                            $address['StreetDescription'] ?? '',
                            $address['BuildingNumber'] ?? ''
                        ]));
                        if (!empty($address['Flat'])) {
                            $label .= ', ' . __('flat number') . ' ' . $address['Flat'];
                        }
                    } elseif ($type === 'WarehouseAddresses') {
                        $label = trim(($address['CityDescription'] ?? '') . '. '
                            . ($address['AddressDescription'] ?? ''));
                    } else {
                        continue;
                    }
                    $options[] = ['value' => $ref, 'label' => $label];
                    $seen[$ref] = true;
                }
            }
        }
        if (count($options) === 1) {
            $options[0]['label'] = __('Nova Poshta returned no saved addresses for this sender contact.');
        }
        return $options;
    }
}
