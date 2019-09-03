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

use Neos\Flow\Log\Exception\CouldNotOpenResourceException;
use Neos\Flow\Log\PlainTextFormatter;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

/**
 * A log backend which writes log entries into a file
 *
 * @api
 */
class FileBackend extends AbstractBackend
{
    /**
     * An array of severity labels, indexed by their integer constant
     * @var array
     */
    protected $severityLabels;

    /**
     * @var string
     */
    protected $logFileUrl = '';

    /**
     * @var integer
     */
    protected $maximumLogFileSize = 0;

    /**
     * @var integer
     */
    protected $logFilesToKeep = 0;

    /**
     * @var boolean
     */
    protected $createParentDirectories = false;

    /**
     * @var boolean
     */
    protected $logMessageOrigin = false;

    /**
     * @var resource
     */
    protected $fileHandle;

    /**
     * Sets URL pointing to the log file. Usually the full directory and
     * the filename, however any valid stream URL is possible.
     *
     * @param string $logFileUrl URL pointing to the log file
     * @return void
     * @api
     */
    public function setLogFileURL(string $logFileUrl): void
    {
        $this->logFileUrl = $logFileUrl;
    }

    /**
     * Sets the flag telling if parent directories in the path leading to
     * the log file URL should be created if they don't exist.
     *
     * The default is to not create parent directories automatically.
     *
     * @param boolean $flag true if parent directories should be created
     * @return void
     * @api
     */
    public function setCreateParentDirectories(bool $flag): void
    {
        $this->createParentDirectories = ($flag === true);
    }

    /**
     * Sets the maximum log file size, if the logfile is bigger, a new one
     * is started.
     *
     * @param int $maximumLogFileSize Maximum size in bytes
     * @return void
     * @api
     * @see setLogFilesToKeep()
     */
    public function setMaximumLogFileSize(int $maximumLogFileSize): void
    {
        $this->maximumLogFileSize = $maximumLogFileSize;
    }

    /**
     * If a new log file is started, keep this number of old log files.
     *
     * @param int $logFilesToKeep Number of old log files to keep
     * @return void
     * @api
     * @see setMaximumLogFileSize()
     */
    public function setLogFilesToKeep(int $logFilesToKeep): void
    {
        $this->logFilesToKeep = $logFilesToKeep;
    }

    /**
     * If enabled, a hint about where the log message was created is added to the
     * log file.
     *
     * @param boolean $flag
     * @return void
     * @api
     */
    public function setLogMessageOrigin(bool $flag): void
    {
        $this->logMessageOrigin = ($flag === true);
    }

    /**
     * Carries out all actions necessary to prepare the logging backend, such as opening
     * the log file or opening a database connection.
     *
     * @return void
     * @throws CouldNotOpenResourceException
     * @throws FilesException
     * @api
     */
    public function open(): void
    {
        $this->severityLabels = [
            LOG_EMERG => 'EMERGENCY',
            LOG_ALERT => 'ALERT    ',
            LOG_CRIT => 'CRITICAL ',
            LOG_ERR => 'ERROR    ',
            LOG_WARNING => 'WARNING  ',
            LOG_NOTICE => 'NOTICE   ',
            LOG_INFO => 'INFO     ',
            LOG_DEBUG => 'DEBUG    ',
        ];

        if (file_exists($this->logFileUrl) && $this->maximumLogFileSize > 0 && filesize($this->logFileUrl) > $this->maximumLogFileSize) {
            $this->rotateLogFile();
        }

        if (file_exists($this->logFileUrl)) {
            $this->fileHandle = fopen($this->logFileUrl, 'ab');
        } else {
            $logPath = dirname($this->logFileUrl);
            if (!file_exists($logPath) || (!is_dir($logPath) && !is_link($logPath))) {
                if ($this->createParentDirectories === false) {
                    throw new CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access because the parent directory does not exist.', 1243931200);
                }
                Files::createDirectoryRecursively($logPath);
            }

            $this->fileHandle = fopen($this->logFileUrl, 'ab');
            if ($this->fileHandle === false) {
                throw new CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access.', 1243588980);
            }

            $streamMeta = stream_get_meta_data($this->fileHandle);
            if ($streamMeta['wrapper_type'] === 'plainfile') {
                fclose($this->fileHandle);
                chmod($this->logFileUrl, 0666);
                $this->fileHandle = fopen($this->logFileUrl, 'ab');
            }
        }
        if ($this->fileHandle === false) {
            throw new CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access.', 1229448440);
        }
    }

    /**
     * Rotate the log file and make sure the configured number of files
     * is kept.
     *
     * @return void
     */
    protected function rotateLogFile(): void
    {
        if (file_exists($this->logFileUrl . '.lock')) {
            return;
        } else {
            touch($this->logFileUrl . '.lock');
        }

        if ($this->logFilesToKeep === 0) {
            unlink($this->logFileUrl);
        } else {
            for ($logFileCount = $this->logFilesToKeep; $logFileCount > 0; --$logFileCount) {
                $rotatedLogFileUrl = $this->logFileUrl . '.' . $logFileCount;
                if (file_exists($rotatedLogFileUrl)) {
                    if ($logFileCount == $this->logFilesToKeep) {
                        unlink($rotatedLogFileUrl);
                    } else {
                        rename($rotatedLogFileUrl, $this->logFileUrl . '.' . ($logFileCount + 1));
                    }
                }
            }
            rename($this->logFileUrl, $this->logFileUrl . '.1');
        }

        unlink($this->logFileUrl . '.lock');
    }

    /**
     * Appends the given message along with the additional information into the log.
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
    public function append(string $message, int $severity = LOG_INFO, $additionalData = null, string $packageKey = null, string $className = null, string $methodName = null): void
    {
        if ($severity > $this->severityThreshold) {
            return;
        }

        if (function_exists('posix_getpid')) {
            $processId = ' ' . str_pad((string)posix_getpid(), 10);
        } else {
            $processId = ' ' . str_pad((string)getmypid(), 10);
        }
        $ipAddress = ($this->logIpAddress === true) ? str_pad(($_SERVER['REMOTE_ADDR'] ?? ''), 15) : '';
        $severityLabel = $this->severityLabels[$severity] ?? 'UNKNOWN  ';
        $output = strftime('%y-%m-%d %H:%M:%S', time()) . $processId . ' ' . $ipAddress . $severityLabel . ' ' . str_pad((string)$packageKey, 20) . ' ' . $message;

        if ($this->logMessageOrigin === true && ($className !== null || $methodName !== null)) {
            $output .= ' [logged in ' . $className . '::' . $methodName . '()]';
        }
        if (!empty($additionalData)) {
            $output .= PHP_EOL . (new PlainTextFormatter($additionalData))->format();
        }
        if ($this->fileHandle !== false) {
            fputs($this->fileHandle, $output . PHP_EOL);
        }
    }

    /**
     * Carries out all actions necessary to cleanly close the logging backend, such as
     * closing the log file or disconnecting from a database.
     *
     * Note: for this backend we do nothing here and rely on PHP to close the filehandle
     * when the request ends. This is to allow full logging until request end.
     *
     * @return void
     * @api
     */
    public function close(): void
    {
    }
}
