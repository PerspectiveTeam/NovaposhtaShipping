<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier\Method;

use Perspective\NovaposhtaShipping\Api\Data\ShippingProcessorInterface;
use Psr\Log\LoggerInterface as Logger;

class AbstractDelivery implements ShippingProcessorInterface
{
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    protected $cityRepository;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\AreaAndRegion
     */
    protected $areaAndRegion;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\ServiceType
     */
    protected $serviceType;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    protected $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\Sender
     */
    protected $sender;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi
     */
    protected $novaposhtaApi;

    /**
     * @return \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    public function getCityRepository(): \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
    {
        return $this->cityRepository;
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Model\Carrier\Sender
     */
    public function getSender(): \Perspective\NovaposhtaShipping\Model\Carrier\Sender
    {
        return $this->sender;
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi
     */
    public function getNovaposhtaApi(): \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi
    {
        return $this->novaposhtaApi;
    }

    /**
     * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public function getTimezone(): \Magento\Framework\Stdlib\DateTime\TimezoneInterface
    {
        return $this->timezone;
    }

    /**
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi $novaposhtaApi
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\AreaAndRegion $areaAndRegion
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\ServiceType $serviceType
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository,
        \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender,
        \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi $novaposhtaApi,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Perspective\NovaposhtaShipping\Model\Carrier\AreaAndRegion $areaAndRegion,
        \Perspective\NovaposhtaShipping\Model\Carrier\ServiceType $serviceType,
        \Perspective\NovaposhtaShipping\Helper\Config $config,
        Logger $logger
    ) {
        $this->cityRepository = $cityRepository;
        $this->sender = $sender;
        $this->novaposhtaApi = $novaposhtaApi;
        $this->timezone = $timezone;
        $this->areaAndRegion = $areaAndRegion;
        $this->serviceType = $serviceType;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getPrice($quote, $object)
    {
        list($defaultSaleSenderCounterparty, $defaultSaleContactSenderData, $defaultSaleContactSenderAddressData) = $this->getSender()->prepareSender();
        $cityRecipientString = $this->getCityRepository()->getCityByCityRef(
            $object->getCurrentUserAddress()->getCity() ?? '8d5a980d-391c-11dd-90d9-001a92567626'
        )->getDescriptionUa() ?: 'Київ';
        $Firstname = $quote->getShippingAddress()->getFirstname() ?: 'C';
        $LastName = $quote->getShippingAddress()->getLastname() ?: 'К';
        $MiddleName = $quote->getShippingAddress()->getMiddlename() ?: 'Н';
        $Phone = $quote->getShippingAddress()->getTelephone() ?: '0961111111';
        $recipientType = 'PrivatePerson';
        $AreaAndRegionData = $this->getAreaAndRegionData($cityRecipientString);
        list($areaRecipient, $regionRecipient) = $this->prepareAreaAndRegionData(
            $object->getCurrentUserAddress()->getCity(),
            $AreaAndRegionData
        );
        $object->setDefaultSaleSenderCounterparty($defaultSaleSenderCounterparty);
        $object->setDefaultSaleContactSenderData($defaultSaleContactSenderData);
        $object->setDefaultSaleContactSenderAddressData($defaultSaleContactSenderAddressData);
        $object->setCityRecipientString($cityRecipientString);
        $object->setFirstname($Firstname);
        $object->setLastName($LastName);
        $object->setMiddleName($MiddleName);
        $object->setPhone($Phone);
        $object->setRecipientType($recipientType);
        $object->setAreaRecipient($areaRecipient);
        $object->setRegionRecipient($regionRecipient);
        $paymentMethod = $quote->getPayment()->getMethod() == 'cashondelivery' ? "Cash" : "NonCash";
        $object->setPaymentMethod($paymentMethod);
        $object->setOrderId($quote->getId());
        $object->setServiceType($this->serviceType->getServiceType($object));
        return [$quote, $object];
    }

    public function isApplicable($shippingCode)
    {
        return true;
    }

    /**
     * @param array $AreaAndRegionData
     * @param string $destinationCityRef
     * @return array
     */
    protected function prepareAreaAndRegionData($destinationCityRef, $AreaAndRegionData = []): array
    {
        $areaRecipient = '';
        $regionRecipient = '';
        if (is_array($AreaAndRegionData) && array_key_exists('success', $AreaAndRegionData)) {
            if (isset($AreaAndRegionData['data'][0]['Addresses'])){
                foreach ($AreaAndRegionData['data'][0]['Addresses'] as $inx => $datum) {
                    if ($datum['DeliveryCity'] === $destinationCityRef) {
                        $areaRecipient = $datum['Area'];
                        $regionRecipient = $datum['Region'];
                    }
                }
            }
        }
        if (!is_array($AreaAndRegionData)) {
            $this->logger->critical('Не вдалося отримати дані про міста та регіони', ['AreaAndRegionData' => $AreaAndRegionData]);
        }
        return [$areaRecipient, $regionRecipient];
    }

    /**
     * @param $cityRecipientString
     * @return mixed
     */
    private function getAreaAndRegionData($cityRecipientString)
    {
        return $this->areaAndRegion->getAreaAndRegionData($cityRecipientString);
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Helper\Config
     */
    public function getConfig(): \Perspective\NovaposhtaShipping\Helper\Config
    {
        return $this->config;
    }
}
