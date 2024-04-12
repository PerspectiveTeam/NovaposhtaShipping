<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Serialize\SerializerInterface;
use Perspective\NovaposhtaCatalog\Api\Data\CityInterface;
use Perspective\NovaposhtaShipping\Api\SenderRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCities;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPerson;
use Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPersonAddress;

class SenderRepository implements SenderRepositoryInterface
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCities
     */
    private SenderRepository\SenderCities $senderCities;

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
     * @param \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderCities $senderCities
     * @param \Perspective\NovaposhtaShipping\Model\SenderRepository\SenderContactPerson $senderContactPerson
     */
    public function __construct(
        SerializerInterface $serializer,
        SenderCities $senderCities,
        SenderContactPerson $senderContactPerson,
        SenderContactPersonAddress $senderContactPersonAddress
    ) {
        $this->serializer = $serializer;
        $this->senderCities = $senderCities;
        $this->senderContactPerson = $senderContactPerson;
        $this->senderContactPersonAddress = $senderContactPersonAddress;
    }

    public function getSenderCities($term = null)
    {
        $result = $this->senderCities->get($term);
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
