<?php
declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class OperationsCache extends TagScope
{

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     */
    public function __construct(
        FrontendPool $cacheFrontendPool
    ) {
        parent::__construct($cacheFrontendPool->get(\Perspective\NovaposhtaShipping\Service\Cache\OperationsCache::TYPE_IDENTIFIER), \Perspective\NovaposhtaShipping\Service\Cache\OperationsCache::CACHE_TAG);
    }
}
