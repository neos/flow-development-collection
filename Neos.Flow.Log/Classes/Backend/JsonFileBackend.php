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
 * A log backend which writes log entries into a file in an easy to parse json format
 *
 * @api
 */
class JsonFileBackend extends FileBackend
{
    /**
     * @param string $message The message to log
     * @param int $severity One of the LOG_* constants
     * @param mixed $additionalData A variable containing more information about the event to be logged
     * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
     * @param string $className Name of the class triggering the log (determined automatically if not specified)
     * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
     * @return void
     */
    public function append(string $message, int $severity = LOG_INFO, $additionalData = null, string $packageKey = null, string $className = null, string $methodName = null): void
    {

        if ($severity > $this->severityThreshold) {
            return;
        }

        if (function_exists('posix_getpid')) {
            $processId = posix_getpid();
        } else {
            $processId = 0;
        }

        $remoteIp = ($this->logIpAddress === true) ? (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') : '';
        $severityLabel = (isset($this->severityLabels[$severity])) ? strtolower(trim($this->severityLabels[$severity])) : 'unknown';

        $logEntryData = [
            'timestamp' => date(\DateTime::ATOM),
            'processId' => $processId,
            'severity' => $severityLabel,
            'remoteIp' => $remoteIp,
            'message' => $message,
            'origin' => [
                'packageKey' => $packageKey,
                'className' => $className,
                'methodName' => $methodName
            ],
            'additionalData' => $additionalData
        ];

        $output = json_encode($logEntryData);

        if ($this->fileHandle !== false) {
            fputs($this->fileHandle, $output . PHP_EOL);
        }
    }
}
