<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log\Backend;

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
 * A log backend which writes log entries into a file
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class FileBackend extends \F3\FLOW3\Log\Backend\AbstractBackend {

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
	protected $createParentDirectories = FALSE;

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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setLogFileURL($logFileUrl) {
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setCreateParentDirectories($flag) {
		$this->createParentDirectories = ($flag === TRUE);
	}

	/**
	 * Sets the maximum log file size, if the logfile is bigger, a new one
	 * is started.
	 *
	 * @param integer $maximumLogFileSize Maximum size in bytes
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 * @see setLogFilesToKeep()
	 */
	public function setMaximumLogFileSize($maximumLogFileSize) {
		$this->maximumLogFileSize = $maximumLogFileSize;
	}

	/**
	 * If a new log file is started, keep this number of old log files.
	 *
	 * @param integer $logFilesToKeep Number of old log files to keep
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 * @see setMaximumLogFileSize()
	 */
	public function setLogFilesToKeep($logFilesToKeep) {
		$this->logFilesToKeep = $logFilesToKeep;
	}

	/**
	 * Carries out all actions necessary to prepare the logging backend, such as opening
	 * the log file or opening a database connection.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function open() {
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
			$this->fileHandle = fopen($this->logFileUrl, 'ab');
		} else {
			$logPath = dirname($this->logFileUrl);
			if (!is_dir($logPath)) {
				if ($this->createParentDirectories === FALSE) throw new \F3\FLOW3\Log\Exception\CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access because the parent directory does not exist.', 1243931200);
				\F3\FLOW3\Utility\Files::createDirectoryRecursively($logPath);
			}

			$this->fileHandle = fopen($this->logFileUrl, 'ab');
			if ($this->fileHandle === FALSE) throw new \F3\FLOW3\Log\Exception\CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access.', 1243588980);

			$streamMeta = stream_get_meta_data($this->fileHandle);
			if ($streamMeta['wrapper_type'] === 'plainfile') {
				fclose($this->fileHandle);
				chmod($this->logFileUrl, 0666);
				$this->fileHandle = fopen($this->logFileUrl, 'ab');
			}
		}
		if ($this->fileHandle === FALSE) throw new \F3\FLOW3\Log\Exception\CouldNotOpenResourceException('Could not open log file "' . $this->logFileUrl . '" for write access.', 1229448440);
	}

	/**
	 * Rotate the log file and make sure the configured number of files
	 * is kept.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function rotateLogFile() {
		if (file_exists($this->logFileUrl . '.lock')) {
			return;
		} else {
			touch($this->logFileUrl . '.lock');
		}

		if ($this->logFilesToKeep === 0) {
			unlink($this->logFileUrl);
		} else {
			for ($logFileCount = $this->logFilesToKeep; $logFileCount > 0; --$logFileCount ) {
				$rotatedLogFileUrl =  $this->logFileUrl . '.' . $logFileCount;
				if (file_exists($rotatedLogFileUrl)) {
					if ($logFileCount == $this->logFilesToKeep) {
						unlink($rotatedLogFileUrl);
					} else {
						rename($rotatedLogFileUrl, $this->logFileUrl . '.' . ($logFileCount+1));
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
	 * @param integer $severity One of the LOG_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function append($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		if ($severity > $this->severityThreshold) {
			return;
		}

		$severityLabel = (isset($this->severityLabels[$severity])) ? $this->severityLabels[$severity] : 'UNKNOWN  ';
		$output = strftime ('%y-%m-%d %H:%M:%S', time()) . ' ' . $severityLabel . ' ' . str_pad($packageKey, 20) . ' ' . $message . PHP_EOL;
		if (!empty($additionalData)) {
			$output .= $this->getFormattedVarDump($additionalData) . PHP_EOL;
		}
		if ($this->fileHandle !== FALSE) {
			fputs($this->fileHandle, $output);
		}
	}

	/**
	 * Carries out all actions necessary to cleanly close the logging backend, such as
	 * closing the log file or disconnecting from a database.
	 *
	 * @return void
	 * @api
	 */
	public function close() {
		fclose($this->fileHandle);
		$this->fileHandle = FALSE;
	}

	/**
	 * Returns a suitable form of a variable (be it a string, array, object ...) for logfile output
	 *
	 * @param mixed $var The variable
	 * @param integer $spaces Number of spaces to add before a line
	 * @return string text output
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getFormattedVarDump($var, $spaces=4) {
		if ($spaces > 100) {
			return NULL;
		}
		$output = '';
		if (is_array($var)) {
			foreach ($var as $k=>$v) {
				if (is_array($v)) {
					$output .= str_repeat(' ',$spaces) . $k . ' => array (' . PHP_EOL . $this->getFormattedVarDump($v, $spaces+3) . str_repeat (' ', $spaces) . ')' . PHP_EOL;
				} else {
					if (is_object($v)) {
						$output .= str_repeat(' ', $spaces) . $k . ' => object: ' . get_class($v) . PHP_EOL;
					} else {
						$output .= str_repeat(' ',$spaces) . $k . ' => ' . $v . PHP_EOL;
					}
				}
			}
		} else {
			if (is_object($var)) {
				$output .= str_repeat(' ', $spaces) . ' [ OBJECT: ' . strtoupper(get_class($var)) . ' ]:' . PHP_EOL;
				if (is_array(get_object_vars ($var))) {
					foreach (get_object_vars ($var) as $objVarName => $objVarValue) {
						if (is_array($objVarValue) || is_object($objVarValue)) {
							$output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . PHP_EOL;
							$output .= $this->getFormattedVarDump($objVarValue, $spaces+3);
						} else {
							$output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . $objVarValue . PHP_EOL;
						}
					}
				}
				$output .= PHP_EOL;
			} else {
				$output .= str_repeat(' ', $spaces) . '=> ' . $var . PHP_EOL;
			}
		}
		return $output;
	}
}
?>