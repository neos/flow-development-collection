<?php
namespace Neos\Cache\Psr\Cache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Cache\CacheItemInterface;

/**
 * A cache item (entry).
 * This is not to be created by user libraries. Instead request an item from the pool (frontend).
 * @see CachePool
 */
class CacheItem implements CacheItemInterface
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var bool
     */
    protected $hit;


    /**
     * @var \DateTime|null
     */
    protected $expirationDate;

    /**
     * Construct item.
     *
     * @param string $key
     * @param bool $hit
     * @param mixed $value
     */
    public function __construct(string $key, bool $hit, $value = null)
    {
        $this->key = $key;
        $this->hit = $hit;
        if ($hit === false) {
            $value = null;
        }
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isHit()
    {
        return $this->hit;
    }

    /**
     * @param mixed $value
     * @return CacheItem|static
     */
    public function set($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param \DateTimeInterface|null $expiration
     * @return CacheItem|static
     */
    public function expiresAt($expiration)
    {
        $this->expirationDate = null;
        if ($expiration instanceof \DateTimeInterface) {
            $this->expirationDate = $expiration;
        }

        return $this;
    }

    /**
     * @param \DateInterval|int|null $time
     * @return CacheItem|static
     */
    public function expiresAfter($time)
    {
        $expiresAt = null;
        if ($time instanceof \DateInterval) {
            $expiresAt = (new \DateTime())->add($time);
        }

        if (is_int($time)) {
            $expiresAt = new \DateTime('@' . (time() + $time));
        }

        return $this->expiresAt($expiresAt);
    }

    /**
     * @return \DateTime|null
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }
}
