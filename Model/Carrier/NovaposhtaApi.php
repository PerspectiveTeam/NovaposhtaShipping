<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

class NovaposhtaApi
{
    /**
     * @var \Perspective\NovaposhtaShipping\Api\NovaPoshtaApi2Factory
     */
    private $novaPoshtaApi2Factory;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @param \Perspective\NovaposhtaShipping\Helper\Config $config
     * @param \Perspective\NovaposhtaShipping\Api\NovaPoshtaApi2Factory $novaPoshtaApi2Factory
     */
    public function __construct(
        \Perspective\NovaposhtaShipping\Helper\Config $config,
        \Perspective\NovaposhtaShipping\Api\NovaPoshtaApi2Factory $novaPoshtaApi2Factory
    ) {
        $this->novaPoshtaApi2Factory = $novaPoshtaApi2Factory;
        $this->config = $config;
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Api\NovaPoshtaApi2
     */
    public function getApi()
    {
        if ($this->config->getShippingConfigByCode('novaposhtashipping', 'api_key')) {
            return $this->novaPoshtaApi2Factory->create(
                ['key' => $this->config->getShippingConfigByCode('novaposhtashipping', 'api_key')]
            );
        } else {
            return $this->novaPoshtaApi2Factory->create(
                ['key' => __('You_should_obtain_your_key_at_novaposhta.ua')->getText()]
            );
        }
    }
}
