<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory;

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
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory
     */
    private CollectionFactory $counterpartyContactPersonCollectionFactory;

    /**
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     */
    public function __construct(
        \Perspective\NovaposhtaShipping\Helper\Config $config,
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository,
        CollectionFactory $counterpartyContactPersonCollectionFactory

    ) {
        $this->config = $config;
        $this->cityRepository = $cityRepository;
        $this->counterpartyContactPersonCollectionFactory = $counterpartyContactPersonCollectionFactory;
    }

    /**
     * @return void
     */
    public function prepareSenderCity()
    {
        $senderCityArr = explode(',', $this->config->getShippingConfigByCode('novaposhtashipping', 'sender_city') ?? '');
        if (count($senderCityArr) === 0 || !$senderCityArr[0]) {
            //сітіІд 4 - Київ
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

    /**
     * @param $counterparty
     * @param $citySender
     * @return array
     */
    public function searchCounterpartyAddress($counterparty, $citySender)
    {
        //todo recheck this collection
        /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $value */
        $counterpartyIndexCollection = $this->counterpartyContactPersonCollectionFactory->create()
            ->addFieldToFilter('counterpartyRef', ['like' => $counterparty])
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
