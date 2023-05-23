<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;

class Sender
{

    /**
     * @var array
     */
    private $senderCityListObject;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private $cityRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory
     */
    private $counterpartyAddressIndexCollectionFactory;

    /**
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     */
    public function __construct(
        \Perspective\NovaposhtaShipping\Helper\Config $config,
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory $counterpartyAddressIndexCollectionFactory
    ) {
        $this->config = $config;
        $this->cityRepository = $cityRepository;
        $this->counterpartyAddressIndexCollectionFactory = $counterpartyAddressIndexCollectionFactory;
    }

    public function prepareSenderCity()
    {
        $senderCityArr = explode(',', $this->config->getShippingConfigByCode('novaposhtashipping', 'sender_city') ?? '');
        if (count($senderCityArr) === 0 || !$senderCityArr[0]) {
            //ситиИд 4 - Киев
            $senderCityArr[] = '4';
        }
        foreach ($senderCityArr as $idx => $value) {
            if (!$value) {
                continue;
            }
            $this->senderCityListObject[$idx] = $this->cityRepository->getCityByCityId((int)$value);
        }
    }

    /**
     * @return array
     */
    public function prepareSender(): array
    {
        $saleSender = $this->config->getShippingConfigByCode('novaposhtashipping', 'sale_sender');
        $defaultSaleSenderCounterparty = '';
        if ($saleSender) {
            $saleSenderData = explode(',', $saleSender);
            $defaultSaleSenderCounterparty = $saleSenderData[0];
            $defaultSaleCitySenderCounterparty = $saleSenderData[1];
        }
        $defaultSaleContactSenderData = '';
        $saleContactSender = $this->config->getShippingConfigByCode('novaposhtashipping', 'sale_sender_contact');
        if ($saleContactSender) {
            $defaultSaleContactSenderData = $saleContactSender;
        }
        $saleContactAddressSender = $this->config->getShippingConfigByCode('novaposhtashipping', 'sale_sender_contact_address');
        $defaultSaleContactSenderAddressData = '';
        if ($saleContactAddressSender) {
            $defaultSaleContactSenderAddressData = $saleContactAddressSender;
        }
        return array($defaultSaleSenderCounterparty, $defaultSaleContactSenderData, $defaultSaleContactSenderAddressData);
    }

    /**
     * @return array
     */
    public function getSenderCityListArray(): array
    {
        return $this->senderCityListObject;
    }

    public function searchCounterpartyAddress($counterparty, $citySender)
    {
        /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex $value */
        $counterpartyIndexCollection = $this->counterpartyAddressIndexCollectionFactory->create()
            ->addFieldToFilter('CounterpartyRef', ['like' => $counterparty])
            ->getItems();
        $result = [];
        foreach ($counterpartyIndexCollection as $index => $value) {
            if ($value->getDescription()
                && $value->getCityDescription()
                && $value->getStreetDescription()
                && $value->getBuildingDescription()
                && $value->getCounterpartyRef()
                && $value->getCityRef() === $citySender
            ) {
                $result[$index]['description'] =
                    $value->getDescription()
                    . ', ' .
                    $value->getCityDescription()
                    . ' ' .
                    $value->getAddressName()
                    . ', ' .
                    $value->getStreetDescription()
                    . ', ' .
                    $value->getBuildingDescription()/*. ', ' .
                    $value->getCounterpartyRef()*/
                ;
                $result[$index]['ref'] = $value->getRef();
            }
        }
        return $result;
    }
}
