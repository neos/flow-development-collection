<?php
namespace TYPO3\Flow\Log\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * A log backend which writes log entries into a file
 *
 * @api
 */
class FileBackend extends \TYPO3\Flow\Log\Backend\AbstractBackend
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
    public function setLogFileURL($logFileUrl)
    {
        $this->logFileUrl = $logFileUrl;
    }

    /**
     * Sets the flag telling if parent directories in the path leading to
     * the log file URL should be created if they don't exist.
     *
     * The default is to not create parent directories automatically.
     *
     * @param boolean $flag TRUE if parent directories should be created
     * @return void
     * @api
     */
    public function setCreateParentDirectories($flag)
    {
        $this->createParentDirectories = ($flag === true);
    }

    /**
     * Sets the maximum log file size, if the logfile is bigger, a new one
     * is started.
     *
     * @param integer $maximumLogFileSize Maximum size in bytes
     * @return void
     * @api
     * @see setLogFilesToKeep()
     */
    public function setMaximumLogFileSize($maximumLogFileSize)
    {
        $this->maximumLogFileSize = $maximumLogFileSize;
    }

    /**
     * If a new log file is started, keep this number of old log files.
     *
     * @param integer $logFilesToKeep Number of old log files to keep
     * @return void
     * @api
     * @see setMaximumLogFileSize()
     */
    public function setLogFilesToKeep($logFilesToKeep)
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
    public function setLogMessageOrigin($flag)
    {
        $this->logMessageOrigin = ($flag === true);
    }

    /**
     * Tries to open the fileHandle for the logFileUrl and throws an exception on error
     * @throws \TYPO3\Flow\Log\Exception\CouldNotOpenResourceException
     */
    private function tryOpenFileHandle()
    {
        $this->fileHandle = fopen($this->logFileUrl, 'ab');
        if ($this->fileHandle === false) {
            throw new \TYPO3\Flow\Log\Exception\CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access.', 1243588980);
        }
    }

    /**
     * Carries out all actions necessary to prepare the logging backend, such as opening
     * the log file or opening a database connection.
     *
     * @return void
     * @throws \TYPO3\Flow\Log\Exception\CouldNotOpenResourceException
     * @api
     */
    public function open()
    {
        $this->severityLabels = array(
            LOG_EMERG   => 'EMERGENCY',
            LOG_ALERT   => 'ALERT    ',
            LOG_CRIT    => 'CRITICAL ',
            LOG_ERR     => 'ERROR    ',
            LOG_WARNING => 'WARNING  ',
            LOG_NOTICE  => 'NOTICE   ',
            LOG_INFO    => 'INFO     ',
            LOG_DEBUG   => 'DEBUG    ',
        );

        if (file_exists($this->logFileUrl) && $this->maximumLogFileSize > 0 && filesize($this->logFileUrl) > $this->maximumLogFileSize) {
            $this->rotateLogFile();
        }

        if (file_exists($this->logFileUrl)) {
            $this->tryOpenFileHandle();
            return;
        }
        $logPath = dirname($this->logFileUrl);
        if (!file_exists($logPath) || (!is_dir($logPath) && !is_link($logPath))) {
            if ($this->createParentDirectories === false) {
                throw new \TYPO3\Flow\Log\Exception\CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access because the parent directory does not exist.', 1243931200);
            }
            \TYPO3\Flow\Utility\Files::createDirectoryRecursively($logPath);
        }

        $this->tryOpenFileHandle();

        $streamMeta = stream_get_meta_data($this->fileHandle);
        if ($streamMeta['wrapper_type'] === 'plainfile') {
            fclose($this->fileHandle);
            chmod($this->logFileUrl, 0666);
            $this->tryOpenFileHandle();
        }
    }

    /**
     * Rotate the log file and make sure the configured number of files
     * is kept.
     *
     * @return void
     */
    protected function rotateLogFile()
    {
        if (file_exists($this->logFileUrl . '.lock')) {
            return;
        }
        touch($this->logFileUrl . '.lock');

        if ($this->logFilesToKeep === 0) {
            unlink($this->logFileUrl);
            unlink($this->logFileUrl . '.lock');
            return;
        }

        for ($logFileCount = $this->logFilesToKeep; $logFileCount > 0; --$logFileCount) {
            $rotatedLogFileUrl =  $this->logFileUrl . '.' . $logFileCount;
            if (file_exists($rotatedLogFileUrl)) {
                if ($logFileCount == $this->logFilesToKeep) {
                    unlink($rotatedLogFileUrl);
                } else {
                    rename($rotatedLogFileUrl, $this->logFileUrl . '.' . ($logFileCount + 1));
                }
            }
        }
        rename($this->logFileUrl, $this->logFileUrl . '.1');
        unlink($this->logFileUrl . '.lock');
    }

    /**
     * Appends the given message along with the additional information into the log.
     *
     * @param string $message The message to log
     * @param integer $severity One of the LOG_* constants
     * @param mixed $additionalData A variable containing more information about the event to be logged
     * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
     * @param string $className Name of the class triggering the log (determined automatically if not specified)
     * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
     * @return void
     * @api
     */
    public function append($message, $severity = LOG_INFO, $additionalData = null, $packageKey = null, $className = null, $methodName = null)
    {
        if ($severity > $this->severityThreshold) {
            return;
        }

        if (function_exists('posix_getpid')) {
            $processId = ' ' . str_pad(posix_getpid(), 10);
        } else {
            $processId = ' ';
        }
        $ipAddress = ($this->logIpAddress === true) ? str_pad((isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''), 15) : '';
        $severityLabel = (isset($this->severityLabels[$severity])) ? $this->severityLabels[$severity] : 'UNKNOWN  ';
        $output = strftime('%y-%m-%d %H:%M:%S', time()) . $processId . ' ' . $ipAddress . $severityLabel . ' ' . str_pad($packageKey, 20) . ' ' . $message;

        if ($this->logMessageOrigin === true && ($className !== null || $methodName !== null)) {
            $output .= ' [logged in ' . $className . '::' . $methodName . '()]';
        }
        if (!empty($additionalData)) {
            $output .= PHP_EOL . $this->getFormattedVarDump($additionalData);
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
     * @todo revise upon resolution of http://forge.typo3.org/issues/9861
     */
    public function close()
    {
    }
}
