<?php
namespace Neos\Flow\Persistence\Doctrine;

use Neos\Cache\Psr\Cache\CacheItem;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context;
use Psr\Cache\CacheItemInterface;

/**
 * Extended PSR cache pool to include security context hash into cache key
 */
class CachePool extends \Neos\Cache\Psr\Cache\CachePool
{
    #[Flow\Inject]
    protected Context $securityContext;

    public function getItem(string $key): CacheItemInterface
    {
        return parent::getItem($this->enrichCacheItemKey($key));
    }

    public function hasItem(string $key): bool
    {
        return parent::hasItem($this->enrichCacheItemKey($key));
    }

    public function deleteItem(string $key): bool
    {
        return parent::deleteItem($this->enrichCacheItemKey($key));
    }

    public function save(CacheItemInterface $item): bool
    {
        $newKey = $this->enrichCacheItemKey($item->getKey());
        $newItem = new CacheItem($newKey, true, $item->get());
        if ($item instanceof CacheItem) {
            $expiresAt = $item->getExpirationDate();
            $newItem->expiresAt($expiresAt);
        }
        if ($item instanceof \Doctrine\Common\Cache\Psr6\CacheItem) {
            $newItem->expiresAt($item->getExpiry() !== null ? new \DateTime('@' . $item->getExpiry()) : null);
        }
        return parent::save($item);
    }

    protected function enrichCacheItemKey(string $key): string
    {
        return md5($key . '|' . $this->securityContext->getContextHash());
    }
}
