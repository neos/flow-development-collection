<?php

namespace Neos\Flow\Monitor;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cache\CacheManager;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use Neos\Flow\Monitor\ChangeDetectionStrategy\StrategyWithMarkDeletedInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Utility\Files;
use Psr\Log\LoggerInterface;

/**
 * A monitor which detects changes in directories or files
 *
 * @api
 */
class FileMonitor
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var ChangeDetectionStrategyInterface
     */
    protected $changeDetectionStrategy;

    /**
     * @var Dispatcher
     */
    protected $signalDispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StringFrontend
     */
    protected $cache;

    /**
     * @var array
     */
    protected $monitoredFiles = [];

    /**
     * @var array
     */
    protected $monitoredDirectories = [];

    /**
     * Changed files for this monitor
     *
     * @var array
     */
    protected $changedFiles = null;

    /**
     * The changed paths for this monitor
     *
     * @var array
     */
    protected $changedPaths = null;

    /**
     * Array of directories and files that were cached on the last run.
     *
     * @var array
     */
    protected $directoriesAndFiles = null;

    /**
     * Constructs this file monitor
     *
     * @param string $identifier Name of this specific file monitor - will be used in the signals emitted by this monitor.
     * @api
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Helper method to create a FileMonitor instance during boot sequence as injections have to be done manually.
     *
     * @param string $identifier
     * @param Bootstrap $bootstrap
     * @return FileMonitor
     */
    public static function createFileMonitorAtBoot($identifier, Bootstrap $bootstrap)
    {
        $fileMonitorCache = $bootstrap->getEarlyInstance(CacheManager::class)->getCache('Flow_Monitor');

        $fileMonitor = new FileMonitor($identifier);
        $fileMonitor->injectCache($fileMonitorCache);
        $fileMonitor->injectSignalDispatcher($bootstrap->getEarlyInstance(Dispatcher::class));
        $fileMonitor->injectLogger($bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class)->get('systemLogger'));

        return $fileMonitor;
    }

    /**
     * Injects the Change Detection Strategy
     *
     * @param ChangeDetectionStrategyInterface $changeDetectionStrategy The strategy to use for detecting changes
     * @return void
     */
    public function injectChangeDetectionStrategy(ChangeDetectionStrategyInterface $changeDetectionStrategy)
    {
        $this->changeDetectionStrategy = $changeDetectionStrategy;
        $this->changeDetectionStrategy->setFileMonitor($this);
    }

    /**
     * Injects the Singal Slot Dispatcher because classes of the Monitor subpackage cannot be proxied by the AOP
     * framework because it is not initialized at the time the monitoring is used.
     *
     * @param Dispatcher $signalDispatcher The Signal Slot Dispatcher
     * @return void
     */
    public function injectSignalDispatcher(Dispatcher $signalDispatcher)
    {
        $this->signalDispatcher = $signalDispatcher;
    }

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Injects the Flow_Monitor cache
     *
     * @param StringFrontend $cache
     * @return void
     */
    public function injectCache(StringFrontend $cache)
    {
        $this->cache = $cache;
    }

    private static function callWatchman($watchmanSocket, $query)
    {
        fwrite($watchmanSocket, json_encode($query) . "\n");
        $responseString = fgets($watchmanSocket);
        return json_decode($responseString, true);
    }

    /**
     * Returns the identifier of this monitor
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Adds the specified file to the list of files to be monitored.
     * The file in question does not necessarily have to exist.
     *
     * @param string $pathAndFilename Absolute path and filename of the file to monitor
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function monitorFile($pathAndFilename)
    {
        if (!is_string($pathAndFilename)) {
            throw new \InvalidArgumentException('String expected, ' . gettype($pathAndFilename), ' given.', 1231171809);
        }
        $pathAndFilename = Files::getUnixStylePath($pathAndFilename);
        if (array_search($pathAndFilename, $this->monitoredFiles) === false) {
            $this->monitoredFiles[] = $pathAndFilename;
        }
    }

    /**
     * Adds the specified directory to the list of directories to be monitored.
     * All files in these directories will be monitored too.
     *
     * @param string $path Absolute path of the directory to monitor
     * @param string $filenamePattern A pattern for filenames to consider for file monitoring (regular expression)
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function monitorDirectory($path, $filenamePattern = null)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('String expected, ' . gettype($path), ' given.', 1231171810);
        }
        $path = Files::getNormalizedPath(Files::getUnixStylePath($path));
        if (!array_key_exists($path, $this->monitoredDirectories)) {
            $this->monitoredDirectories[$path] = $filenamePattern;
        }
    }

    /**
     * Returns a list of all monitored files
     *
     * @return array A list of paths and filenames of monitored files
     * @api
     */
    public function getMonitoredFiles()
    {
        return $this->monitoredFiles;
    }

    /**
     * Returns a list of all monitored directories
     *
     * @return array A list of paths of monitored directories
     * @api
     */
    public function getMonitoredDirectories()
    {
        return array_keys($this->monitoredDirectories);
    }

    protected $currentClock = 'c:0:0';

    /**
     * Detects changes of the files and directories to be monitored and emits signals
     * accordingly.
     *
     * @return void
     * @api
     */
    public function detectChanges()
    {
        $t1 = microtime(true);
        $watchmanSocket = $this->getWatchmanSocket();

        $since = $this->cache->get($this->identifier . '_since') ?: 'c:0:0';

        if (count($this->monitoredFiles) != 0) {
            throw new \Exception('TODO: Monitored files not supported');
        }

        $expression = ['anyof'];
        foreach ($this->monitoredDirectories as $directory => $regex) {
            if ($regex !== null) {
                $expression[] = ['allof',
                    ['dirname', rtrim(substr($directory, strlen(FLOW_PATH_PACKAGES)), '/')],
                    ['pcre', $regex]
                ];
            } else {
                $expression[] = ['dirname', rtrim(substr($directory, strlen(FLOW_PATH_PACKAGES)), '/')];
            }
        }
        if (count($expression) === 1) {
            // no part of expression exists
            return;
        }

        $query = ['query', rtrim(FLOW_PATH_PACKAGES, '/'), [
            'since' => $since,
            'fields' => ['name', 'exists', 'new'],
            'expression' => $expression
        ]];

        $response = self::callWatchman($watchmanSocket, $query);
        if (isset($response['error']) && strpos($response['error'], 'is not watched') !== -1) {
            var_dump(self::callWatchman($watchmanSocket, ['watch', rtrim(FLOW_PATH_PACKAGES, '/')]));
            $response = self::callWatchman($watchmanSocket, $query);
        }

        $changedFiles = [];
        foreach ($response['files'] as $file) {
            $status = ChangeDetectionStrategyInterface::STATUS_CHANGED;
            if ($file['exists'] === false) {
                $status = ChangeDetectionStrategyInterface::STATUS_DELETED;
            } elseif($file['new'] === true) {
                $status = ChangeDetectionStrategyInterface::STATUS_CREATED;
            }
            $changedFiles[FLOW_PATH_PACKAGES . $file['name']] = $status;
        }
        if (count($changedFiles) > 0) {
            $this->emitFilesHaveChanged($this->identifier, $changedFiles);
        }


        if (count($changedFiles) > 0) {
            $this->logger->info(sprintf('File Monitor "%s" detected %s changed files and %s changed directories.', $this->identifier, count($changedFiles), 0));
        }
        $this->currentClock = $response['clock'];
        /*if ($changedFileCount > 0) {

        if ($changedPathCount > 0) {
            $this->emitDirectoriesHaveChanged($this->identifier, $this->changedPaths);
        }
        */
    }

    /**
     * Signalizes that the specified file has changed
     *
     * @param string $monitorIdentifier Name of the monitor which detected the change
     * @param array $changedFiles An array of changed files (key = path and filename) and their status (value)
     * @return void
     * @api
     */
    protected function emitFilesHaveChanged($monitorIdentifier, array $changedFiles)
    {
        $this->signalDispatcher->dispatch(FileMonitor::class, 'filesHaveChanged', [$monitorIdentifier, $changedFiles]);
    }

    /**
     * Signalizes that the specified directory has changed
     *
     * @param string $monitorIdentifier Name of the monitor which detected the change
     * @param array $changedDirectories An array of changed directories (key = path) and their status (value)
     * @return void
     * @api
     */
    protected function emitDirectoriesHaveChanged($monitorIdentifier, array $changedDirectories)
    {
        $this->signalDispatcher->dispatch(FileMonitor::class, 'directoriesHaveChanged', [$monitorIdentifier, $changedDirectories]);
    }

    /**
     * Caches the directories and their files
     *
     * @return void
     */
    public function shutdownObject()
    {
        $this->cache->set($this->getIdentifier() . '_since', $this->currentClock);
    }

    private static $watchmanSocket = null;

    private function getWatchmanSocket()
    {
        if (!self::$watchmanSocket) {
            $socketPathAndFilename = $this->cache->get('watchmanSocketPathAndFilename');
            if (!$socketPathAndFilename || !file_exists($socketPathAndFilename)) {
                $resultArr = [];
                exec('watchman get-sockname', $resultArr);
                $result = json_decode(join('', $resultArr), true);
                $socketPathAndFilename = $result['sockname'];
                $this->cache->set('watchmanSocketPathAndFilename', $socketPathAndFilename);
            }
            $errno = 0;
            $errstr = '';
            self::$watchmanSocket = stream_socket_client('unix://' . $socketPathAndFilename, $errno, $errstr);
            if ($errno != 0) {
                throw new \Neos\Flow\Exception('Watchman socket not openable, error was ' . $errno . ': ' . $errstr);
            }
        }
        return self::$watchmanSocket;
    }
}
