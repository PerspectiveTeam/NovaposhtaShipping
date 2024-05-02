<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Magento\Framework\DataObject;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaShipping\Helper\Config;
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
        Config $config,
        CityRepositoryInterface $cityRepository,
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
        if ($saleSender && strpos($saleSender, ',') !== false) {
            $saleSenderData = explode(',', $saleSender);
            $defaultSaleSenderCounterparty = $saleSenderData[0];
        } else {
            $defaultSaleSenderCounterparty = $saleSender;
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
     * @return array
     */
    public function searchCounterpartyAddress($counterparty)
    {
        //todo recheck this collection
        /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $value */
        $counterpartyIndexCollection = $this->counterpartyContactPersonCollectionFactory->create()
            ->addFieldToFilter('counterpartyRef', ['like' => $counterparty])
            ->getItems();
        $result = [];
        $counter = 0;
        foreach ($counterpartyIndexCollection as $index => $value) {
            $addressesArr = is_string($value->getAddresses()) ? json_decode($value->getAddresses()) : $value->getAddresses();
            if (is_array($addressesArr)) {
                foreach ($addressesArr as $addressTypeName => $addressType) {
                    foreach ($addressType as $address) {
                        $counter++;
                        $addressObj = new DataObject($address);
                        if ($addressTypeName == 'DoorsAddresses') {
                            $result[$counter]['description'] = sprintf(
                                "%s. %s %s %s %s %s %s ",
                                $addressObj->getData('SettlementDescription'),
                                $addressObj->getData('StreetsType'),
                                $addressObj->getData('StreetsDescription'),
                                __('building number'),
                                $addressObj->getData('BuildingNumber'),
                                __('flat number'),
                                $addressObj->getData('Flat'),
                            );
                        }
                        if ($addressTypeName == 'WarehouseAddresses') {
                            $result[$counter]['description'] = sprintf(
                                "%s. %s",
                                $addressObj->getData('CityDescription'),
                                $addressObj->getData('AddressDescription'),

                            );
                        }
                        $result[$counter]['ref'] = $addressObj->getData('Ref');
                    }
                }
            }
        }
        return $result;
    }
}
