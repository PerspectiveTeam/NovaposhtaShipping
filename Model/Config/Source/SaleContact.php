<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Source;
class SaleContact
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
        $valueInAdmin = $this->novaposhtaHelper->getStoreConfigByCode('novaposhtashipping', 'sale_sender');
        $options[] = ['value' => '', 'label' => __('Select default contact person')];
        if ($valueInAdmin) {
            $data = explode(',', $valueInAdmin);
            $response = $this->novaposhtaHelper->getApi()->getCounterpartyContactPersons($data[0]);
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
    }
}
