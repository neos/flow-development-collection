<?php
namespace TYPO3\Flow\Utility\Lock;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A Semaphore based lock strategy.
 *
 * This lock strategy is based on Sempahore.
 *
 * @see http://php.net/manual/fr/ref.sem.php
 */
class SemaphoreLockStrategy implements LockStrategyInterface
{
    /**
     * Mutex semaphore
     *
     * @var resource
     */
    protected $mutex;

    /**
     * Read/write semaphore
     *
     * @var resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock.
     * @return void
     */
    public function acquire($subject, $exclusiveLock)
    {
        $this->initializeSemaphore($subject);
        $this->subject = $subject;
        sem_acquire($this->resource);
    }

    /**
     * @param string $subject
     * @throws LockNotAcquiredException
     */
    protected function initializeSemaphore($subject)
    {
        if ($this->mutex === null) {
            $mutexKey = crc32($subject . ':mutex');
            $this->mutex = sem_get($mutexKey, 1);
            if ($this->mutex === false) {
                throw new LockNotAcquiredException('Unable to get mutex semaphore', 1455653589);
            }
        }

        if ($this->resource === null) {
            $resourceKey = crc32($subject . ':resource');
            $this->resource = sem_get($resourceKey, 1);
            if ($this->resource === false) {
                throw new LockNotAcquiredException('Unable to get resource semaphore', 1455653593);
            }
        }
    }

    /**
     * @return boolean
     */
    public function release()
    {
        @sem_release($this->resource);
    }

    /**
     * @param resource $resource is a semaphore resource, obtained from <b>sem_get</b>
     * @param callable $callback
     */
    protected function semCallback($resource, $callback)
    {
        sem_acquire($resource);
        $callback();
        sem_release($resource);
    }
}
