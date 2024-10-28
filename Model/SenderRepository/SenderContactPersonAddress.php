<?php

namespace Perspective\NovaposhtaShipping\Model\SenderRepository;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Perspective\NovaposhtaShipping\Model\Config\Source\SaleContactAddress;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory;

class SenderContactPersonAddress
{
    private CollectionFactory $collectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private SaleContactAddress $saleContactAddress;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SaleContactAddress $saleContactAddress
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->saleContactAddress = $saleContactAddress;
    }

    public function get()
    {
        $senderCounterpartyValue = $this->scopeConfig->getValue('carriers/novaposhtashipping/sale_sender_contact_address');
        $allSenders = $this->saleContactAddress->toOptionArray();
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
                'label' => 'Contact Person Address is not specified in the module settings, or you use the Physical Person Account. Please, check it out in the module settings.'
            ];
    }
}
