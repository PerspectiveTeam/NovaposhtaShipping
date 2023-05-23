<?php

namespace Perspective\NovaposhtaShipping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_MYMODULE = 'carriers/';

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    protected function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $code
     * @param null $storeId
     * @return mixed
     */
    public function getShippingConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_MYMODULE . 'novaposhtashipping/' . $code, $storeId);
    }

    /**
     * @param $code
     * @param null $storeId
     * @return mixed
     */
    public function getShippingConfigByCode($key, $code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_MYMODULE . $key . '/' . $code, $storeId);
    }

    /**
     * @param null $storeId
     * @return string '1' - if enabled, '0' otherwise
     */
    public function getIsEnabledConfig($storeId = null)
    {
        return $this->getShippingConfig('active', $storeId);
    }

    public function getAllowedShippingMethods($storeId = null)
    {
        return $this->getShippingConfig('allowed_methods', $storeId);
    }

    public function getAllowedShippingMethodsAsArray($storeId = null)
    {
        return explode(',', $this->getAllowedShippingMethods($storeId = null));
    }
}
