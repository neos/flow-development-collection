<?php
declare(strict_types=1);

namespace Neos\Flow\Log\Backend;

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
 * A backend which just ignores everything
 *
 * @api
 */
class NullBackend extends AbstractBackend
{
    /**
     * Does nothing
     *
     * @return void
     * @api
     */
    public function open(): void
    {
    }

    /**
     * Ignores the call
     *
     * @param string $message The message to log
     * @param int $severity One of the LOG_* constants
     * @param mixed $additionalData A variable containing more information about the event to be logged
     * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
     * @param string $className Name of the class triggering the log (determined automatically if not specified)
     * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
     * @return void
     * @api
     */
    public function append(string $message, int $severity = 1, $additionalData = null, string $packageKey = null, string $className = null, string $methodName = null): void
    {
    }

    /**
     * Does nothing
     *
     * @return void
     * @api
     */
    public function close(): void
    {
    }
}
