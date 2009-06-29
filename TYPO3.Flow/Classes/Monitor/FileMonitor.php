<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Monitor;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 */

/**
 * A monitor which detects changes in directories or files
 *
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class FileMonitor {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface
	 */
	protected $changeDetectionStrategy;

	/**
	 * @var \F3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Injects the Change Detection Strategy
	 *
	 * @param F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface $changeDetectionStrategy The strategy to use for detecting changes
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectChangeDetectionStrategy(\F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface $changeDetectionStrategy) {
		$this->changeDetectionStrategy = $changeDetectionStrategy;
	}

	/**
	 * Injects the Singal Slot Dispatcher because classes of the Monitor subpackage cannot be proxied by the AOP
	 * framework because it is not initialized at the time the monitoring is used.
	 *
	 * @param \F3\FLOW3\SignalSlot\Dispatcher $signalSlotDispatcher The Signal Slot Dispatcher
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectSignalSlotDispatcher(\F3\FLOW3\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Injects the FLOW3_Monitor cache
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this monitor
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function monitorFile($pathAndFilename) {
		if (!is_string($pathAndFilename)) throw new \InvalidArgumentException('String expected, ' . gettype($pathAndFilename), ' given.', 1231171809);
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function monitorDirectory($path) {
		if (!is_string($path)) throw new \InvalidArgumentException('String expected, ' . gettype($path), ' given.', 1231171810);
		$path = rtrim($path, '/');
		if (array_search($path, $this->monitoredDirectories) === FALSE) {
			$this->monitoredDirectories[] = $path;
		}
	}

	/**
	 * Returns a list of all monitored files
	 *
	 * @return array A list of paths and file names of monitored files
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMonitoredFiles() {
		return $this->monitoredFiles;
	}

	/**
	 * Returns a list of all monitored directories
	 *
	 * @return array A list of paths of monitored directories
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMonitoredDirectories() {
		return $this->monitoredDirectories;
	}

	/**
	 * Detects changes of the files and directories to be monitored and emits signals
	 * accordingly.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChanges() {
		$changedFiles = array();
		$changedDirectories = array();

		$changedFiles = $this->detectChangedFiles($this->monitoredFiles);

		foreach ($this->monitoredDirectories as $path) {
			if (!isset($this->directoriesAndFiles[$path])) {
				$this->directoriesAndFiles[$path] = \F3\FLOW3\Utility\Files::readDirectoryRecursively($path);
				$this->directoriesChanged = TRUE;
				$changedDirectories[$path] = \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED;
			}
		}

		foreach ($this->directoriesAndFiles as $path => $pathAndFilenames) {
			$changedFiles = array_merge($changedFiles, $this->detectChangedFiles($pathAndFilenames));
			if (!is_dir($path)) {
				unset($this->directoriesAndFiles[$path]);
				$this->directoriesChanged = TRUE;
				$changedDirectories[$path] = \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED;
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function detectChangedFiles(array $pathAndFilenames) {
		$changedFiles = array();
		foreach ($pathAndFilenames as $pathAndFilename) {
			$status = $this->changeDetectionStrategy->getFileStatus($pathAndFilename);
			if ($status !== \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitFilesHaveChanged($monitorIdentifier, array $changedFiles) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__, func_get_args());
	}

	/**
	 * Signalizes that the specified directory has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param array $changedFiles An array of changed directories (key = path) and their status (value)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitDirectoriesHaveChanged($monitorIdentifier, array $changedDirectories) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__, func_get_args());
	}

	/**
	 * Caches the directories and their files
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function shutdownObject() {
		if ($this->directoriesChanged === TRUE) {
			$this->cache->set('directoriesAndFiles', $this->directoriesAndFiles);
		}
	}
}
?>