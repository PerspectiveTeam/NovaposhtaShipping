<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Source;

use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;

class SaleContactAddress
{
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;

    /**
     * @var bool
     */
    private $allowProceed;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\Sender
     */
    private $sender;

    /**
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender
     */
    public function __construct(
        NovaposhtaHelper $novaposhtaHelper,
        \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->sender = $sender;
    }

    public function toOptionArray($isMultiselect = false)
    {
        $senderInAdmin = $this->novaposhtaHelper->getStoreConfigByCode('novaposhtashipping', 'sale_sender') ?? '';
        $options[] = ['value' => '-3', 'label' => __('Select default contact person address')];
        $cityData = explode(',', $senderInAdmin);
        if (isset($cityData[0]) && isset($cityData[1])) {
            $citySender = $cityData[1];
            $counterpartyRef = $cityData[0];
            $this->allowProceed = true;
        }

        if ($senderInAdmin && $this->allowProceed) {
            // $data = explode(',', $senderInAdmin);
            $result = $this->sender->searchCounterpartyAddress($counterpartyRef, $citySender);
            if ($result) {
                foreach ($result as $counterpartyAddressIndex => $counterpartyAddressValue) {
                    $options[] =
                        [
                            'label' => $counterpartyAddressValue ['description'],
                            'value' => $counterpartyAddressValue['ref']
                        ];
                }
                return $options;
            } else {
                return [['label' => __('Firstly you need to specify sale sender contact'), 'value' => -1]];
            }
        }

    }
}
