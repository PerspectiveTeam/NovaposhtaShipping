<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

class ShippingSales
{
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     */
    public function __construct(\Perspective\NovaposhtaShipping\Helper\Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $result
     * @param string $cargoType
     * @param float $subtotal
     * @param $optionsSeat
     * @return array
     */
    public function calculateShippingSales(array $result, string $cargoType, float $subtotal, $optionsSeat): array
    {
        if (isset($result)) {
            if (!empty($this->config->getShippingConfigByCode('novaposhtashipping', 'lathing'))
                && $cargoType === 'Pallet'
            ) {
                if ($subtotal < $this->config->getShippingConfigByCode('novaposhtashipping', 'free_cost')) {
                    $result['price'] += count($optionsSeat) * $this->config->getShippingConfigByCode(
                        'novaposhtashipping',
                        'lathing'
                    );
                }
            }
            if (isset($result['lathing'])) {
                if (!empty($this->config->getShippingConfigByCode('novaposhtashipping', 'lathing'))
                    && $cargoType === 'Cargo'
                ) {
                    if ($subtotal < $this->config->getShippingConfigByCode('novaposhtashipping', 'free_cost')) {
                        if (!empty($result['lathing']) && count($optionsSeat) < count($result['lathing'])) {
                            $result['price'] += count($optionsSeat) * $this->config->getShippingConfigByCode(
                                'novaposhtashipping',
                                'lathing'
                            );
                        } else {
                            $result['price'] += count($result['lathing']) * $this->config->getShippingConfigByCode(
                                'novaposhtashipping',
                                'lathing'
                            );
                        }
                    }
                }
            }
            //Бесплатная доставка при сумме товара от определенной суммы и при отсутсвии признака товара со спешл прайсом
            if (!empty($this->config->getShippingConfigByCode('novaposhtashipping', 'free_cost'))
                && isset($result['sale']) && count($result['sale']) === 0
            ) {
                if ($subtotal >= $this->config->getShippingConfigByCode('novaposhtashipping', 'free_cost')) {
                    $result['price'] = 0;
                }
            }
        }
        return $result;
    }
}
