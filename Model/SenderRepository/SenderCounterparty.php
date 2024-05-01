<?php

namespace Perspective\NovaposhtaShipping\Model\SenderRepository;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaCatalog\Api\Data\CityInterface;
use Perspective\NovaposhtaShipping\Model\Config\Source\Sale;

class SenderCounterparty
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    private Sale $sale;


    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Perspective\NovaposhtaShipping\Model\Config\Source\Sale $sale
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Sale $sale
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->sale = $sale;
    }

    /**
     * @return array
     */
    public function get()
    {
        $senderCounterpartyValue = $this->scopeConfig->getValue('carriers/novaposhtashipping/sale_sender');
        $allSenders = $this->sale->toOptionArray();
        foreach ($allSenders as $key => $value) {
            if ($value['value'] === $senderCounterpartyValue) {
                return
                    [
                        'value' => $senderCounterpartyValue,
                        'label' => trim($value['label'])
                    ];
            }
        }
        return
            [
                'value' => '-1',
                'label' => 'Main Sender is not specified in the module settings. Please, specify it in the module settings.'
            ];
    }

}
