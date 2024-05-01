<?php

namespace Perspective\NovaposhtaShipping\Model\SenderRepository;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Perspective\NovaposhtaShipping\Model\Config\Source\SaleContact;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory;

class SenderContactPerson
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    private SaleContact $saleContactPerson;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SaleContact $saleContactPerson
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->saleContactPerson = $saleContactPerson;
    }

    public function get()
    {
        $senderCounterpartyValue = $this->scopeConfig->getValue('carriers/novaposhtashipping/sale_sender_contact');
        $allSenders = $this->saleContactPerson->toOptionArray();
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
                'label' => 'Contact Person of the Sender is not specified in the module settings. Please, specify it in the module settings.'
            ];
    }
}
