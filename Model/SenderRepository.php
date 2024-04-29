<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaShipping\Api\SenderRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCounterparty;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPerson;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPersonAddress;

class SenderRepository implements SenderRepositoryInterface
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private SerializerInterface $serializer;

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
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCounterparty $senderCounterparty
     * @param \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPerson $senderContactPerson
     */
    public function __construct(
        SerializerInterface $serializer,
        SenderCounterparty $senderCounterparty,
        SenderContactPerson $senderContactPerson,
        SenderContactPersonAddress $senderContactPersonAddress
    ) {
        $this->serializer = $serializer;
        $this->senderCounterparty = $senderCounterparty;
        $this->senderContactPerson = $senderContactPerson;
        $this->senderContactPersonAddress = $senderContactPersonAddress;
    }

    public function getSenderCounterparty($term = null)
    {
        $result = $this->senderCounterparty->get($term);
        return $this->serializer->serialize($result);
    }

    public function getSenderContactPerson($term = null)
    {
        $result = $this->senderContactPerson->get($term);
        return $this->serializer->serialize($result);
    }

    public function getSenderContactPersonAddress($term = null)
    {
        $result = $this->senderContactPersonAddress->get($term);
        return $this->serializer->serialize($result);
    }
}
