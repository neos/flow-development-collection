<?php
namespace Neos\Cache\Psr;

use Psr\Cache\CacheItemInterface;

/**
 * A cache item (entry).
 * This is not to be created by user libraries. Instead request an item from the pool (frontend).
 */
class PsrCacheItem implements CacheItemInterface
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
     * @return PsrCacheItem|static
     */
    public function set($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param \DateTimeInterface|null $expiration
     * @return PsrCacheItem|static
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
     * @return PsrCacheItem|static
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
