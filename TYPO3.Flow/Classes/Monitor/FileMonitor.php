<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Monitor;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
	 * @var F3\FLOW3\Monitor\ChangeDetectionStrategyInterface
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
	 * @var array
	 */
	protected $monitoredFiles = array();

	/**
	 * @var array
	 */
	protected $monitoredDirectories = array();

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
	 * @param F3\FLOW3\Monitor\ChangeDetectionStrategyInterface $changeDetectionStrategy The strategy to use for detecting changes
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectChangeDetectionStrategy(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface $changeDetectionStrategy) {
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
		$filesCounter = 0;
		$directoriesCounter = 0;
		foreach ($this->monitoredFiles as $pathAndFilename) {
			$status = $this->changeDetectionStrategy->getFileStatus($pathAndFilename);
			if ($status !== \F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
				$filesCounter ++;
				$this->emitFileHasChanged($this->identifier, $pathAndFilename, $status);
			}
		}
		foreach ($this->monitoredDirectories as $path) {
			$status = $this->changeDetectionStrategy->getDirectoryStatus($path);
			if ($status !== \F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_UNCHANGED) {
				$directoriesCounter ++;
				$this->emitDirectoryHasChanged($this->identifier, $path, $status);
			}
		}
		if ($filesCounter > 0 || $directoriesCounter > 0) $this->systemLogger->log(sprintf('File Monitor detected %s changed files and %s changed directories.', $filesCounter, $directoriesCounter), LOG_INFO);
	}

	/**
	 * Signalizes that the specified file has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param string $pathAndFilename Path and name of the file
	 * @param integer $status Details of the change, one of the F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitFileHasChanged($monitorIdentifier, $pathAndFilename, $status) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__, func_get_args());
	}

	/**
	 * Signalizes that the specified directory has changed
	 *
	 * @param string $monitorIdentifier Name of the monitor which detected the change
	 * @param string $path Path to the directory
	 * @param integer $status Details of the change, one of the F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitDirectoryHasChanged($monitorIdentifier, $path, $status) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__, func_get_args());
	}
}
?>