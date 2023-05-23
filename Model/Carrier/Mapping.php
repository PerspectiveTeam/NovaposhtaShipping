<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Magento\Framework\Exception\LocalizedException;
use Perspective\NovaposhtaShipping\Api\Data\ProcessorInterface;
use Perspective\NovaposhtaShipping\Helper\Config;

class Mapping
{
    /**
     * @var \Perspective\NovaposhtaShipping\Api\Data\GeneralShippingInterface
     */
    protected $data;

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Config
     */
    private $config;

    /**
     * @param array<\Perspective\NovaposhtaShipping\Api\Data\GeneralShippingInterface> $data
     */
    public function __construct(
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        foreach ($data as $warehouseCode => $classObject) {
            if (in_array($warehouseCode, $this->config->getAllowedShippingMethodsAsArray())) {
                $this->data = $data;
            }
        }
    }

    public function getShippingMethodClasses()
    {
        return $this->data;
    }

    public function getShippingMethodClassByCode($code)
    {
        if (!$code) {
            return reset($this->data);
        }
        return $this->data[$code];
    }
}
