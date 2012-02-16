<?php
namespace TYPO3\FLOW3\Monitor;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

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
	 * @var \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface
	 */
	protected $changeDetectionStrategy;

	/**
	 * @var \TYPO3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $signalDispatcher;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
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
	 * @var array
	 */
	protected $directoriesAndFiles = array();

	/**
	 * If the directories changed and therefore need to be cached
	 * @var boolean
	 */
	protected $directoriesChanged = FALSE;

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
	 * Injects the Change Detection Strategy
	 *
	 * @param \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface $changeDetectionStrategy The strategy to use for detecting changes
	 * @return void
	 */
	public function injectChangeDetectionStrategy(\TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface $changeDetectionStrategy) {
		$this->changeDetectionStrategy = $changeDetectionStrategy;
	}

	/**
	 * Injects the Singal Slot Dispatcher because classes of the Monitor subpackage cannot be proxied by the AOP
	 * framework because it is not initialized at the time the monitoring is used.
	 *
	 * @param \TYPO3\FLOW3\SignalSlot\Dispatcher $signalDispatcher The Signal Slot Dispatcher
	 * @return void
	 */
	public function injectSignalDispatcher(\TYPO3\FLOW3\SignalSlot\Dispatcher $signalDispatcher) {
		$this->signalDispatcher = $signalDispatcher;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Injects the FLOW3_Monitor cache
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this monitor
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->cache->has('directoriesAndFiles')) {
			$this->directoriesAndFiles = $this->cache->get('directoriesAndFiles');
		}
	}

	/**
	 * Adds the specified file to the list of files to be monitored.
	 * The file in question does not necessarily have to exist.
	 *
	 * @param string $pathAndFilename Absolute path and filename of the file to monitor
	 * @return void
	 * @api
	 */
	public function monitorFile($pathAndFilename) {
		if (!is_string($pathAndFilename)) throw new \InvalidArgumentException('String expected, ' . gettype($pathAndFilename), ' given.', 1231171809);
		$pathAndFilename = \TYPO3\FLOW3\Utility\Files::getUnixStylePath($pathAndFilename);
		if (array_search($pathAndFilename, $this->monitoredFiles) === FALSE) {
			$this->monitoredFiles[] = $pathAndFilename;
		}
	}

	/**
	 * Adds the specified directory to the list of directories to be monitored.
	 * All files in these directories will be monitored too.
	 *
	 * @param string $path Absolute path of the directory to monitor
	 * @return void
	 * @api
	 */
	public function monitorDirectory($path) {
		if (!is_string($path)) throw new \InvalidArgumentException('String expected, ' . gettype($path), ' given.', 1231171810);
		$path = rtrim(\TYPO3\FLOW3\Utility\Files::getUnixStylePath($path), '/');
		if (array_search($path, $this->monitoredDirectories) === FALSE) {
			$this->monitoredDirectories[] = $path;
		}
	}

	/**
	 * Returns a list of all monitored files
	 *
	 * @return array A list of paths and file names of monitored files
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
		return $this->monitoredDirectories;
	}

	/**
	 * Detects changes of the files and directories to be monitored and emits signals
	 * accordingly.
	 *
	 * @return void
	 * @api
	 */
	public function detectChanges() {
		$changedDirectories = array();
		$changedFiles = $this->detectChangedFiles($this->monitoredFiles);

		foreach ($this->monitoredDirectories as $path) {
			if (!isset($this->directoriesAndFiles[$path])) {
				$this->directoriesAndFiles[$path] = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($path);
				$this->directoriesChanged = TRUE;
				$changedDirectories[$path] = \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED;
			}
		}

		foreach ($this->directoriesAndFiles as $path => $pathAndFilenames) {
			if (!is_dir($path)) {
				unset($this->directoriesAndFiles[$path]);
				$this->directoriesChanged = TRUE;
				$changedDirectories[$path] = \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED;
			} else {
				$currentSubDirectoriesAndFiles = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($path);
				if ($currentSubDirectoriesAndFiles != $pathAndFilenames) {
					$pathAndFilenames = array_unique(array_merge($currentSubDirectoriesAndFiles, $pathAndFilenames));
				}
				$changedFiles = array_merge($changedFiles, $this->detectChangedFiles($pathAndFilenames));
			}
		}

		if (count($changedFiles) > 0) {
			$this->emitFilesHaveChanged($this->identifier, $changedFiles);
		}
		if (count($changedDirectories) > 0) {
			$this->emitDirectoriesHaveChanged($this->identifier, $changedDirectories);
		}
		if (count($changedFiles) > 0 || count($changedDirectories) > 0) $this->systemLogger->log(sprintf('File Monitor detected %s changed files and %s changed directories.', count($changedFiles), count($changedDirectories)), LOG_INFO);
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
			if ($status !== \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
				$changedFiles[$pathAndFilename] = $status;
			}
		}
		return $changedFiles;
	}

	/**
	 * Signalizes that the specified file has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param array $changedFiles An array of changed files (key = path and filenmae) and their status (value)
	 * @return void
	 * @FLOW3\Signal
	 * @api
	 */
	protected function emitFilesHaveChanged($monitorIdentifier, array $changedFiles) {
		$this->signalDispatcher->dispatch(__CLASS__, 'filesHaveChanged', array($monitorIdentifier, $changedFiles));
	}

	/**
	 * Signalizes that the specified directory has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param array $changedDirectories An array of changed directories (key = path) and their status (value)
	 * @return void
	 * @FLOW3\Signal
	 * @api
	 */
	protected function emitDirectoriesHaveChanged($monitorIdentifier, array $changedDirectories) {
		$this->signalDispatcher->dispatch(__CLASS__, 'directoriesHaveChanged', array($monitorIdentifier, $changedDirectories));
	}

	/**
	 * Caches the directories and their files
	 *
	 * @return void
	 */
	public function shutdownObject() {
		if ($this->directoriesChanged === TRUE) {
			$this->cache->set('directoriesAndFiles', $this->directoriesAndFiles);
		}
	}
}
?>