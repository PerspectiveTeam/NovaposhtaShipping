<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaShipping\Api\SenderRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCounterparty;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPerson;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPersonAddress;

class SenderRepository implements SenderRepositoryInterface
{
    /**
     * @var \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCounterparty
     */
    private SenderRepository\SenderCounterparty $senderCounterparty;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPerson
     */
    private SenderRepository\SenderContactPerson $senderContactPerson;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPersonAddress
     */
    private SenderRepository\SenderContactPersonAddress $senderContactPersonAddress;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;


    /**
     * @param \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCounterparty $senderCounterparty
     * @param \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPerson $senderContactPerson
     * @param \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPersonAddress $senderContactPersonAddress
     */
    public function __construct(
        SenderCounterparty $senderCounterparty,
        SenderContactPerson $senderContactPerson,
        SenderContactPersonAddress $senderContactPersonAddress,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->senderCounterparty = $senderCounterparty;
        $this->senderContactPerson = $senderContactPerson;
        $this->senderContactPersonAddress = $senderContactPersonAddress;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function getSenderCounterparty()
    {
        $result = $this->senderCounterparty->get();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getSenderContactPerson()
    {
        $result = $this->senderContactPerson->get();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getSenderContactPersonAddress()
    {
        $result = $this->senderContactPersonAddress->get();
        return $result;
    }

    public function isOrganization()
    {
        return $this->scopeConfig->getValue('carriers/novaposhtashipping/is_organization');
    }
}
