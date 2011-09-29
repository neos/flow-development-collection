<?php
namespace TYPO3\FLOW3\Log\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A log backend which writes log entries to the console (STDOUT or STDERR)
 *
 * @api
 * @scope prototype
 */
class ConsoleBackend extends \TYPO3\FLOW3\Log\Backend\AbstractBackend {

	/**
	 * An array of severity labels, indexed by their integer constant
	 * @var array
	 */
	protected $severityLabels;

	/**
	 * Stream name to use (stdout, stderr)
	 * @var string
	 */
	protected $streamName = 'stdout';

	/**
	 * @var resource
	 */
	protected $streamHandle;

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

		$this->streamHandle = fopen('php://' . $this->streamName, 'w');
		if (!is_resource($this->streamHandle)) throw new \TYPO3\FLOW3\Log\Exception\CouldNotOpenResourceException('Could not open stream "' . $this->streamName . '" for write access.', 1310986609);
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
		$output = $severityLabel . ' ' . $message;
		if (!empty($additionalData)) {
			$output .= PHP_EOL . $this->getFormattedVarDump($additionalData);
		}
		if (is_resource($this->streamHandle)) {
			fputs($this->streamHandle, $output . PHP_EOL);
		}
	}

	/**
	 * Carries out all actions necessary to cleanly close the logging backend, such as
	 * closing the log file or disconnecting from a database.
	 *
	 * Note: for this backend we do nothing here and rely on PHP to close the stream handle
	 * when the request ends. This is to allow full logging until request end.
	 *
	 * @return void
	 * @api
	 * @todo revise upon resolution of http://forge.typo3.org/issues/9861
	 */
	public function close() {}

	/**
	 * Returns a suitable form of a variable (be it a string, array, object ...) for logfile output
	 *
	 * @param mixed $var The variable
	 * @param integer $spaces Number of spaces to add before a line
	 * @return string text output
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getFormattedVarDump($var, $spaces = 4) {
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
						$output .= str_repeat(' ',$spaces) . $k . ' => ' . ($v === NULL ? '␀' : $v) . PHP_EOL;
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
							$output .= $this->getFormattedVarDump($objVarValue, $spaces + 3);
						} else {
							$output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . ($objVarValue === NULL ? '␀' : $objVarValue) . PHP_EOL;
						}
					}
				}
				$output .= PHP_EOL;
			} else {
				$output .= str_repeat(' ', $spaces) . '=> ' . ($var === NULL ? '␀' : $var) . PHP_EOL;
			}
		}
		return $output;
	}
}
?>