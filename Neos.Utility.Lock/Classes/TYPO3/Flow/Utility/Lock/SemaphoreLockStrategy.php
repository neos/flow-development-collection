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
 * This lock strategy is based on Sempahore and only supports exclusive locks.
 *
 * @see http://php.net/manual/en/ref.sem.php
 */
class SemaphoreLockStrategy implements LockStrategyInterface
{
    /**
     * Semaphore resource
     *
     * @var resource
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $isAcquired = false;

    /**
     * SemaphoreLockStrategy constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!function_exists('sem_get')) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                throw new UnsupportedStrategyException('The SemaphoreLockStrategy is not supported on Windows, since the PHP Semaphore (sysvsem) extension is not available.', 1455653598);
            }
            throw new UnsupportedStrategyException('The SemaphoreLockStrategy needs the PHP Semaphore (sysvsem) extension to be installed.', 1455653599);
        }
    }

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock.
     * @return void
     */
    public function acquire($subject, $exclusiveLock)
    {
        $this->initializeSemaphore($subject);
        $this->isAcquired = sem_acquire($this->resource);
        if ($this->isAcquired === false) {
            throw new LockNotAcquiredException(sprintf('Could not acquire lock on semaphore for subject "%s".', $subject), 1455653595);
        }
    }

    /**
     * @param string $subject
     * @throws LockNotAcquiredException
     */
    protected function initializeSemaphore($subject)
    {
        if ($this->resource === null) {
            $resourceKey = crc32($subject . ':resource');
            $this->resource = sem_get($resourceKey, 1);
            if ($this->resource === false) {
                throw new LockNotAcquiredException(sprintf('Unable to get resource semaphore with key 0x%x.', $resourceKey), 1455653593);
            }
        }
    }

    /**
     * @return boolean
     */
    public function release()
    {
        if ($this->isAcquired === false) {
            return false;
        }
        $this->isAcquired = false;
        return @sem_release($this->resource);
    }
}
