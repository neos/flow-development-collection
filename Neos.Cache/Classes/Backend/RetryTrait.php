<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Retries failed operations of a cache backend with an exponentially growing
 * back-off time. Useful for backends talking to a server that might be briefly
 * unavailable, e.g. while it is restarting or still loading its data.
 *
 * Provides the backend options:
 *  - retryOnError:   Whether to retry failed operations (default: true)
 *  - maximumRetries: How often to retry before giving up (default: 8)
 *
 * The n-th retry waits n² * 100ms, so 8 retries wait about 20 seconds in total.
 *
 * Backends using this trait must implement isRetryableError() and may override
 * prepareRetry() to e.g. reset a broken connection.
 */
trait RetryTrait
{
    protected bool $retryOnError = true;

    protected int $maximumRetries = 8;

    /**
     * Enables or disables retrying failed operations
     *
     * @api
     */
    public function setRetryOnError(bool|int|string $retryOnError): void
    {
        $this->retryOnError = filter_var($retryOnError, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sets how often a failed operation is retried before giving up
     *
     * @api
     */
    public function setMaximumRetries(int|string $maximumRetries): void
    {
        if ((int)$maximumRetries < 0) {
            throw new \InvalidArgumentException('The maximum number of retries must not be negative', 1731157200);
        }
        $this->maximumRetries = (int)$maximumRetries;
    }

    /**
     * Runs $operation and retries it if it fails with a retryable error
     *
     * @template T
     * @param \Closure(): T $operation
     * @return T
     * @throws \Throwable the last error, if all retries failed or the error is not retryable
     */
    protected function withRetries(\Closure $operation): mixed
    {
        $retries = 0;
        while (true) {
            try {
                return $operation();
            } catch (\Throwable $throwable) {
                if (!$this->retryOnError || $retries >= $this->maximumRetries || !$this->isRetryableError($throwable)) {
                    throw $throwable;
                }
                $retries++;
                $this->waitBeforeRetry($retries);
                $this->prepareRetry($throwable);
            }
        }
    }

    /**
     * Whether the given error might go away when trying again
     */
    abstract protected function isRetryableError(\Throwable $throwable): bool;

    /**
     * Called before an operation is retried
     */
    protected function prepareRetry(\Throwable $throwable): void
    {
    }

    protected function waitBeforeRetry(int $retry): void
    {
        usleep($retry ** 2 * 100000);
    }
}
