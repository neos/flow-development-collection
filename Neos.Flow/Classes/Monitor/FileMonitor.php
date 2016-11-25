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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use Neos\Flow\Monitor\ChangeDetectionStrategy\StrategyWithMarkDeletedInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Utility\Files;

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
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

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

        // The change detector needs to be instantiated and registered manually because
        // it has a complex dependency (cache) but still needs to be a singleton.
        $fileChangeDetector = new ChangeDetectionStrategy\ModificationTimeStrategy();
        $fileChangeDetector->injectCache($fileMonitorCache);
        $bootstrap->getObjectManager()->registerShutdownObject($fileChangeDetector, 'shutdownObject');

        $fileMonitor = new FileMonitor($identifier);
        $fileMonitor->injectCache($fileMonitorCache);
        $fileMonitor->injectChangeDetectionStrategy($fileChangeDetector);
        $fileMonitor->injectSignalDispatcher($bootstrap->getEarlyInstance(Dispatcher::class));
        $fileMonitor->injectSystemLogger($bootstrap->getEarlyInstance(SystemLoggerInterface::class));

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
     * Injects the system logger
     *
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
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

    /**
     * Detects changes of the files and directories to be monitored and emits signals
     * accordingly.
     *
     * @return void
     * @api
     */
    public function detectChanges()
    {
        if ($this->changedFiles === null || $this->changedPaths === null) {
            $this->loadDetectedDirectoriesAndFiles();
            $changesDetected = false;
            $this->changedPaths = $this->changedFiles = [];
            $this->changedFiles = $this->detectChangedFiles($this->monitoredFiles);

            foreach ($this->monitoredDirectories as $path => $filenamePattern) {
                $changesDetected = $this->detectChangesOnPath($path, $filenamePattern) ? true : $changesDetected;
            }

            if ($changesDetected) {
                $this->saveDetectedDirectoriesAndFiles();
            }
            $this->directoriesAndFiles = null;
        }

        $changedFileCount = count($this->changedFiles);
        $changedPathCount = count($this->changedPaths);

        if ($changedFileCount > 0) {
            $this->emitFilesHaveChanged($this->identifier, $this->changedFiles);
        }
        if ($changedPathCount > 0) {
            $this->emitDirectoriesHaveChanged($this->identifier, $this->changedPaths);
        }
        if ($changedFileCount > 0 || $changedPathCount) {
            $this->systemLogger->log(sprintf('File Monitor "%s" detected %s changed files and %s changed directories.', $this->identifier, $changedFileCount, $changedPathCount), LOG_INFO);
        }
    }

    /**
     * Detect changes for one of the monitored paths.
     *
     * @param string $path
     * @param string $filenamePattern
     * @return boolean TRUE if any changes were detected in this path
     */
    protected function detectChangesOnPath($path, $filenamePattern)
    {
        $currentDirectoryChanged = false;
        try {
            $currentSubDirectoriesAndFiles = $this->readMonitoredDirectoryRecursively($path, $filenamePattern);
        } catch (\Exception $exception) {
            $currentSubDirectoriesAndFiles = [];
            $this->changedPaths[$path] = ChangeDetectionStrategyInterface::STATUS_DELETED;
        }

        $nowDetectedFilesAndDirectories = [];
        if (!isset($this->directoriesAndFiles[$path])) {
            $this->directoriesAndFiles[$path] = [];
            $this->changedPaths[$path] = ChangeDetectionStrategyInterface::STATUS_CREATED;
        }

        foreach ($currentSubDirectoriesAndFiles as $pathAndFilename) {
            $status = $this->changeDetectionStrategy->getFileStatus($pathAndFilename);
            if ($status !== ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
                $this->changedFiles[$pathAndFilename] = $status;
                $currentDirectoryChanged = true;
            }

            if (isset($this->directoriesAndFiles[$path][$pathAndFilename])) {
                unset($this->directoriesAndFiles[$path][$pathAndFilename]);
            }
            $nowDetectedFilesAndDirectories[$pathAndFilename] = 1;
        }

        if ($this->directoriesAndFiles[$path] !== []) {
            foreach (array_keys($this->directoriesAndFiles[$path]) as $pathAndFilename) {
                $this->changedFiles[$pathAndFilename] = ChangeDetectionStrategyInterface::STATUS_DELETED;
                if ($this->changeDetectionStrategy instanceof StrategyWithMarkDeletedInterface) {
                    $this->changeDetectionStrategy->setFileDeleted($pathAndFilename);
                } else {
                    // This call is needed to mark the file deleted in any possibly existing caches of the strategy.
                    // The return value is not important as we know this file doesn't exist so we set the status to DELETED anyway.
                    $this->changeDetectionStrategy->getFileStatus($pathAndFilename);
                }
            }
            $currentDirectoryChanged = true;
        }

        if ($currentDirectoryChanged) {
            $this->setDetectedFilesForPath($path, $nowDetectedFilesAndDirectories);
        }

        return $currentDirectoryChanged;
    }

    /**
     * Read a monitored directory recursively, taking into account filename patterns
     *
     * @param string $path The path of a monitored directory
     * @param string $filenamePattern
     * @return \Generator<string> A generator returning filenames with full path
     */
    protected function readMonitoredDirectoryRecursively($path, $filenamePattern)
    {
        $directories = [Files::getNormalizedPath($path)];
        while ($directories !== []) {
            $currentDirectory = array_pop($directories);
            if (is_file($currentDirectory . '.flowFileMonitorIgnore')) {
                continue;
            }
            if ($handle = opendir($currentDirectory)) {
                while (false !== ($filename = readdir($handle))) {
                    if ($filename[0] === '.') {
                        continue;
                    }
                    $pathAndFilename = $currentDirectory . $filename;
                    if (is_dir($pathAndFilename)) {
                        array_push($directories, $pathAndFilename . DIRECTORY_SEPARATOR);
                    } elseif ($filenamePattern === null || preg_match('|' . $filenamePattern . '|', $filename) === 1) {
                        yield $pathAndFilename;
                    }
                }
                closedir($handle);
            }
        }
    }

    /**
     * Loads the last detected files for this monitor.
     *
     * @return void
     */
    protected function loadDetectedDirectoriesAndFiles()
    {
        if ($this->directoriesAndFiles === null) {
            $this->directoriesAndFiles = json_decode($this->cache->get($this->identifier . '_directoriesAndFiles'), true);
            if (!is_array($this->directoriesAndFiles)) {
                $this->directoriesAndFiles = [];
            }
        }
    }

    /**
     * Store the changed directories and files back to the cache.
     *
     * @return void
     */
    protected function saveDetectedDirectoriesAndFiles()
    {
        $this->cache->set($this->identifier . '_directoriesAndFiles', json_encode($this->directoriesAndFiles));
    }

    /**
     * @param string $path
     * @param array $files
     * @return void
     */
    protected function setDetectedFilesForPath($path, array $files)
    {
        $this->directoriesAndFiles[$path] = $files;
    }

    /**
     * Detects changes in the given list of files and emits signals if necessary.
     *
     * @param array $pathAndFilenames A list of full path and filenames of files to check
     * @return array An array of changed files (key = path and filenmae) and their status (value)
     */
    protected function detectChangedFiles(array $pathAndFilenames)
    {
        $changedFiles = [];
        foreach ($pathAndFilenames as $pathAndFilename) {
            $status = $this->changeDetectionStrategy->getFileStatus($pathAndFilename);
            if ($status !== ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
                $changedFiles[$pathAndFilename] = $status;
            }
        }
        return $changedFiles;
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
        $this->changeDetectionStrategy->shutdownObject();
    }
}
