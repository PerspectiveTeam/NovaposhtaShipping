<?php

namespace Perspective\NovaposhtaShipping\Service\Cache;

class OperationsCache
{
    const CACHE_LIFETIME = 3600; // 1 hour

    const TYPE_IDENTIFIER = 'novaposhta_operations_cache';

    const CACHE_TAG = 'NOVAPOSHTA_OPERATIONS_CACHE_TAG';

    /**
     * @var \Perspective\NovaposhtaShipping\Helper\Cache
     */
    protected $cacheHelper;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\App\Cache
     */
    private $cache;


    /**
     * @param \Perspective\NovaposhtaShipping\Helper\Cache $cacheHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\App\Cache $cache
     */
    public function __construct(
        \Perspective\NovaposhtaShipping\Helper\Cache $cacheHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\App\Cache $cache
    ) {
        $this->cacheHelper = $cacheHelper;
        $this->serializer = $serializer;
        $this->cache = $cache;
    }

    /**
     * @param null $cacheId
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function load($cacheId = null)
    {
        if ($this->cacheHelper->isCacheEnabled()) {   /**@phpstan-ignore-next-line  */
            if ($cacheId) {
                return $this->cache->load($cacheId) ? $this->serializer->unserialize($this->cache->load($cacheId)) : false;
            }
            $result = $this->cache->load(self::TYPE_IDENTIFIER);
            if ($result) {
                return $this->serializer->unserialize($result);
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * @param $data
     * @param string $cacheId
     * @param int $cacheLifetime
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save($data, $cacheId = self::TYPE_IDENTIFIER, $cacheLifetime = self::CACHE_LIFETIME): bool
    {
        if ($this->cacheHelper->isCacheEnabled()) {
            $data = $this->serializer->serialize($data);
            $this->cache->save($data, $cacheId, [self::CACHE_TAG, $cacheId], $cacheLifetime);
            return true;
        }
        return false;
    }
}
