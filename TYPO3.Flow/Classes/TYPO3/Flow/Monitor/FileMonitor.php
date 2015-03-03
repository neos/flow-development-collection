<?php
namespace TYPO3\Flow\Monitor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use TYPO3\Flow\Utility\Files;

/**
 * A monitor which detects changes in directories or files
 *
 * @api
 */
class FileMonitor {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var ChangeDetectionStrategyInterface
	 */
	protected $changeDetectionStrategy;

	/**
	 * @var \TYPO3\Flow\SignalSlot\Dispatcher
	 */
	protected $signalDispatcher;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $cache;

	/**
	 * @var array
	 */
	protected $monitoredFiles = array();

	/**
	 * @var array
	 */
	protected $monitoredDirectories = array();

	/**
	 * Changed files for this monitor
	 *
	 * @var array
	 */
	protected $changedFiles = NULL;

	/**
	 * The changed paths for this monitor
	 *
	 * @var array
	 */
	protected $changedPaths = NULL;

	/**
	 * Array of directories and files that were cached on the last run.
	 *
	 * @var array
	 */
	protected $directoriesAndFiles = NULL;

	/**
	 * Constructs this file monitor
	 *
	 * @param string $identifier Name of this specific file monitor - will be used in the signals emitted by this monitor.
	 * @api
	 */
	public function __construct($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Helper method to create a FileMonitor instance during boot sequence as injections have to be done manually.
	 *
	 * @param string $identifier
	 * @param Bootstrap $bootstrap
	 * @return FileMonitor
	 */
	static public function createFileMonitorAtBoot($identifier, Bootstrap $bootstrap) {
		$fileMonitorCache = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Monitor');

		// The change detector needs to be instantiated and registered manually because
		// it has a complex dependency (cache) but still needs to be a singleton.
		$fileChangeDetector = new \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
		$fileChangeDetector->injectCache($fileMonitorCache);
		$bootstrap->getObjectManager()->registerShutdownObject($fileChangeDetector, 'shutdownObject');

		$fileMonitor = new FileMonitor($identifier);
		$fileMonitor->injectCache($fileMonitorCache);
		$fileMonitor->injectChangeDetectionStrategy($fileChangeDetector);
		$fileMonitor->injectSignalDispatcher($bootstrap->getEarlyInstance('TYPO3\Flow\SignalSlot\Dispatcher'));
		$fileMonitor->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface'));

		return $fileMonitor;
	}

	/**
	 * Injects the Change Detection Strategy
	 *
	 * @param ChangeDetectionStrategyInterface $changeDetectionStrategy The strategy to use for detecting changes
	 * @return void
	 */
	public function injectChangeDetectionStrategy(ChangeDetectionStrategyInterface $changeDetectionStrategy) {
		$this->changeDetectionStrategy = $changeDetectionStrategy;
		$this->changeDetectionStrategy->setFileMonitor($this);
	}

	/**
	 * Injects the Singal Slot Dispatcher because classes of the Monitor subpackage cannot be proxied by the AOP
	 * framework because it is not initialized at the time the monitoring is used.
	 *
	 * @param \TYPO3\Flow\SignalSlot\Dispatcher $signalDispatcher The Signal Slot Dispatcher
	 * @return void
	 */
	public function injectSignalDispatcher(\TYPO3\Flow\SignalSlot\Dispatcher $signalDispatcher) {
		$this->signalDispatcher = $signalDispatcher;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Injects the Flow_Monitor cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\StringFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\Flow\Cache\Frontend\StringFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Returns the identifier of this monitor
	 *
	 * @return string
	 */
	public function getIdentifier() {
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
	public function monitorFile($pathAndFilename) {
		if (!is_string($pathAndFilename)) {
			throw new \InvalidArgumentException('String expected, ' . gettype($pathAndFilename), ' given.', 1231171809);
		}
		$pathAndFilename = Files::getUnixStylePath($pathAndFilename);
		if (array_search($pathAndFilename, $this->monitoredFiles) === FALSE) {
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
	public function monitorDirectory($path, $filenamePattern = NULL) {
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
	public function getMonitoredFiles() {
		return $this->monitoredFiles;
	}

	/**
	 * Returns a list of all monitored directories
	 *
	 * @return array A list of paths of monitored directories
	 * @api
	 */
	public function getMonitoredDirectories() {
		return array_keys($this->monitoredDirectories);
	}

	/**
	 * Detects changes of the files and directories to be monitored and emits signals
	 * accordingly.
	 *
	 * @return void
	 * @api
	 */
	public function detectChanges() {
		if ($this->changedFiles === NULL || $this->changedPaths === NULL) {
			$this->loadDetectedDirectoriesAndFiles();
			$changesDetected = FALSE;
			$this->changedPaths = $this->changedFiles = array();
			$this->changedFiles = $this->detectChangedFiles($this->monitoredFiles);

			foreach ($this->monitoredDirectories as $path => $filenamePattern) {
				$changesDetected = $this->detectChangesOnPath($path, $filenamePattern) ? TRUE : $changesDetected;
			}

			if ($changesDetected) {
				$this->saveDetectedDirectoriesAndFiles();
			}
			$this->directoriesAndFiles = NULL;
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
	protected function detectChangesOnPath($path, $filenamePattern) {
		$currentDirectoryChanged = FALSE;
		try {
			$currentSubDirectoriesAndFiles = $this->readMonitoredDirectoryRecursively($path, $filenamePattern);
		} catch (\Exception $exception) {
			$currentSubDirectoriesAndFiles = array();
			$this->changedPaths[$path] = ChangeDetectionStrategyInterface::STATUS_DELETED;
		}

		$nowDetectedFilesAndDirectories = array();
		if (!isset($this->directoriesAndFiles[$path])) {
			$this->directoriesAndFiles[$path] = array();
			$this->changedPaths[$path] = ChangeDetectionStrategyInterface::STATUS_CREATED;
		}

		foreach ($currentSubDirectoriesAndFiles as $pathAndFilename) {
			$status = $this->changeDetectionStrategy->getFileStatus($pathAndFilename);
			if ($status !== ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
				$this->changedFiles[$pathAndFilename] = $status;
				$currentDirectoryChanged = TRUE;
			}

			if (isset($this->directoriesAndFiles[$path][$pathAndFilename])) {
				unset($this->directoriesAndFiles[$path][$pathAndFilename]);
			}
			$nowDetectedFilesAndDirectories[$pathAndFilename] = 1;
		}

		if ($this->directoriesAndFiles[$path] !== array()) {
			foreach (array_keys($this->directoriesAndFiles[$path]) as $pathAndFilename) {
				$this->changedFiles[$pathAndFilename] = ChangeDetectionStrategyInterface::STATUS_DELETED;
				$this->changeDetectionStrategy->setFileDeleted($pathAndFilename);
			}
			$currentDirectoryChanged = TRUE;
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
	protected function readMonitoredDirectoryRecursively($path, $filenamePattern) {
		$directories = array(Files::getNormalizedPath($path));
		while ($directories !== array()) {
			$currentDirectory = array_pop($directories);
			if (is_file($currentDirectory . '.flowFileMonitorIgnore')) {
				continue;
			}
			if ($handle = opendir($currentDirectory)) {
				while (FALSE !== ($filename = readdir($handle))) {
					if ($filename[0] === '.') {
						continue;
					}
					$pathAndFilename = $currentDirectory . $filename;
					if (is_dir($pathAndFilename)) {
						array_push($directories, $pathAndFilename . DIRECTORY_SEPARATOR);
					} elseif ($filenamePattern === NULL || preg_match('|' . $filenamePattern . '|', $filename) === 1) {
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
	protected function loadDetectedDirectoriesAndFiles() {
		if ($this->directoriesAndFiles === NULL) {
			$this->directoriesAndFiles = json_decode($this->cache->get($this->identifier . '_directoriesAndFiles'), TRUE);
			if (!is_array($this->directoriesAndFiles)) {
				$this->directoriesAndFiles = array();
			}
		}
	}

	/**
	 * Store the changed directories and files back to the cache.
	 *
	 * @return void
	 */
	protected function saveDetectedDirectoriesAndFiles() {
		$this->cache->set($this->identifier . '_directoriesAndFiles', json_encode($this->directoriesAndFiles));
	}

	/**
	 * @param string $path
	 * @param array $files
	 * @return void
	 */
	protected function setDetectedFilesForPath($path, array $files) {
		$this->directoriesAndFiles[$path] = $files;
	}

	/**
	 * Detects changes in the given list of files and emits signals if necessary.
	 *
	 * @param array $pathAndFilenames A list of full path and filenames of files to check
	 * @return array An array of changed files (key = path and filenmae) and their status (value)
	 */
	protected function detectChangedFiles(array $pathAndFilenames) {
		$changedFiles = array();
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
	 * @Flow\Signal
	 * @api
	 */
	protected function emitFilesHaveChanged($monitorIdentifier, array $changedFiles) {
		$this->signalDispatcher->dispatch('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', array($monitorIdentifier, $changedFiles));
	}

	/**
	 * Signalizes that the specified directory has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param array $changedDirectories An array of changed directories (key = path) and their status (value)
	 * @return void
	 * @Flow\Signal
	 * @api
	 */
	protected function emitDirectoriesHaveChanged($monitorIdentifier, array $changedDirectories) {
		$this->signalDispatcher->dispatch('TYPO3\Flow\Monitor\FileMonitor', 'directoriesHaveChanged', array($monitorIdentifier, $changedDirectories));
	}

	/**
	 * Caches the directories and their files
	 *
	 * @return void
	 */
	public function shutdownObject() {
		$this->changeDetectionStrategy->shutdownObject();
	}
}
