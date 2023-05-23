<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

class DeliveryDate
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi
     */
    private $novaposhtaApi;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi $novaposhtaApi
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     */
    public function __construct(
        \Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi $novaposhtaApi,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Perspective\NovaposhtaShipping\Helper\Config $config
    ) {
        $this->timezone = $timezone;
        $this->novaposhtaApi = $novaposhtaApi;
        $this->config = $config;
    }

    /**
     * @param $city
     * @param $destinationCityRef
     * @param string $service_type
     * @param $data1
     * @param $lowerShippingPrice
     * @param $datum
     * @return array
     */
    public function calculateDeliveryDate(
        $city,
        $destinationCityRef,
        string $service_type,
        $data1,
        $lowerShippingPrice
    ): array {
        $deliveryDate = $this->getDeliveryDate($city->getRef(), $destinationCityRef, $service_type);
        foreach ($data1 as $datum) {
            if ($datum['Cost'] < $lowerShippingPrice) {
                if ($lowerShippingPrice === INF) {
                    $lowerShippingPrice = 0;
                }
                if (isset($datum['CostRedelivery'])) {
                    if ($datum['CostRedelivery']) {
                        $lowerShippingPrice += $datum['Cost'] + $datum['CostRedelivery'];
                    } else {
                        $lowerShippingPrice += $datum['Cost'];
                    }
                } else {
                    $lowerShippingPrice += $datum['Cost'];
                }
            }
        }
        $lowerShippingPriceArr = [];
        if (isset($deliveryDate)) {
            if (isset($deliveryDate['data'])) {
                if (isset($deliveryDate['data'][0])) {
                    if (isset($deliveryDate['data'][0]['DeliveryDate'])) {
                        if (isset($deliveryDate['data'][0]['DeliveryDate']['date'])) {
                            $lowerShippingPriceArr[$deliveryDate['data'][0]['DeliveryDate']['date']] = $lowerShippingPrice;
                        }
                    }
                }
            }
        }
        return $lowerShippingPriceArr;
    }

    public function getDeliveryDate($sender, $recipient, $type)
    {
//        $this->timezone->date()->format('d.m.Y')
        $date = $this->timezone->date();
        $date->add(
            new \DateInterval('P' . intval($this->config->getShippingConfigByCode(
                    'novaposhtashipping',
                    'shipping_offset'
                )) . 'D')
        );
        return $this->novaposhtaApi->getApi()->request('InternetDocument', 'getDocumentDeliveryDate', [
            'CitySender' => $sender,
            'CityRecipient' => $recipient,
            'ServiceType' => $type,
            'DateTime' => $date->format('d.m.Y')
        ]);
    }
}
