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
 * Contract for a basic throwable logger interface
 *
 * The severities are (according to RFC3164) the PHP constants:
 *   LOG_EMERG   # Emergency: system is unusable
 *   LOG_ALERT   # Alert: action must be taken immediately
 *   LOG_CRIT    # Critical: critical conditions
 *   LOG_ERR     # Error: error conditions
 *   LOG_WARNING # Warning: warning conditions
 *   LOG_NOTICE  # Notice: normal but significant condition
 *   LOG_INFO    # Informational: informational messages
 *   LOG_DEBUG   # Debug: debug-level messages
 *
 * @api
 */
interface ThrowableLoggerInterface extends LoggerInterface
{
    /**
     * Writes information about the given exception into the log.
     *
     * @param \Throwable $throwable The throwable to log
     * @param array $additionalData Additional data to log
     * @return void
     * @api
     */
    public function logThrowable(\Throwable $throwable, array $additionalData = []);
}
