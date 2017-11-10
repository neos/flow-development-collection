<?php
namespace Neos\Flow\Log;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 *
 */
class Logger extends DefaultLogger implements SystemLoggerInterface, SecurityLoggerInterface, ThrowableLoggerInterface
{
    /**
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @param ThrowableStorageInterface $throwableStorage
     */
    public function injectThrowableStorage(ThrowableStorageInterface $throwableStorage)
    {
        $this->throwableStorage = $throwableStorage;
    }

    /**
     * Writes information about the given exception into the log.
     *
     * @param \Exception $exception The exception to log
     * @param array $additionalData Additional data to log
     * @return void
     * @deprecated
     */
    public function logException(\Exception $exception, array $additionalData = [])
    {
        $this->logThrowable($exception, $additionalData);
    }

    /**
     * @param \Throwable $throwable The throwable to log
     * @param array $additionalData Additional data to log
     * @return void
     * @api
     */
    public function logThrowable(\Throwable $throwable, array $additionalData = [])
    {
        $message = $this->throwableStorage->logThrowable($throwable, $additionalData);
        $this->log($message, LOG_ERR);
    }
}
