<?php

namespace Perspective\NovaposhtaShipping\Helper;

use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;

class Cache extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Cache\State
     */
    private $cacheState;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Cache\State $cacheState
     */
    public function __construct(
        Context $context,
        State $cacheState
    ) {
        parent::__construct($context);
        $this->cacheState = $cacheState;
    }

    /**
     * Get config value by path.
     *
     * @param string $path
     * @param int|string|null $storeId
     * @return int|string|null
     */
    public function get(string $path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->cacheState->isEnabled(OperationsCache::TYPE_IDENTIFIER);
    }

}
