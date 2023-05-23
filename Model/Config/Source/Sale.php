<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Source;
class Sale
{
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;

    public function __construct(
        \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
    }


    public function toOptionArray($isMultiselect = false)
    {
        $response = $this->novaposhtaHelper->getApi()->getCounterparties('Sender');
        if (array_key_exists('success', $response)) {
            if ($response['success'] === true) {
                foreach ($response['data'] as $counterpartyFromApiIndex => $counterpartyFromApiValue) {
                    $options[] =
                        [
                            'label' => $counterpartyFromApiValue['Description'] . ' ' . $counterpartyFromApiValue['CityDescription'],
                            'value' => $counterpartyFromApiValue['Ref'] . ',' . $counterpartyFromApiValue['City']
                        ];
                }
                return $options;
            } else {
                return ['label' => __('Firstly you need to specify API key'), 'value' => -1];
            }

        }
    }
}
