<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log;

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
 * @subpackage Log
 * @version $Id$
 */

/**
 * A simple file logger based on the TYPO3 4.x extension rlmp_filedevlog.
 * This has been just a quick development to at least have some logging
 * at all. Enough room for improvement ..
 *
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class SimpleFileLogger implements \F3\FLOW3\Log\LoggerInterface {

	/**
	 * @var array An array of severity labels, indexed by their integer constant
	 */
	private $severityLabels;

	/**
	 * @var string Contains the full path and filename of the log file
	 */
	private $logDirectoryAndFilename;

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($logDirectoryAndFilename = NULL) {
		$this->severityLabels = array(
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_DEBUG => 'DEBUG   ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_INFO  => 'INFO    ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_NOTICE => 'NOTICE  ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_WARNING  => 'WARNING ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_FATAL => 'FATAL   ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_OK => 'OK      ',
		);
		$this->logDirectoryAndFilename = ($logDirectoryAndFilename === NULL) ? FLOW3_PATH_PUBLIC . 'flow3.log' : $logDirectoryAndFilename;
	}

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value: -1 (debug), 0 (ok), 1 (info), 2 (notice), 3 (warning), or 4 (fatal)
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function log($message, $severity = 1, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {

		$output = strftime ('%y-%m-%d %H:%M:%S', time()) . ' ' . $this->severityLabels[$severity] . ' ' . str_pad($packageKey, 20) . ' ' . $message . chr(10);
		if (!empty($additionalData)) {
			$output .= $this->getFormattedVarDump($additionalData) . chr(10);
		}
		$fh = fopen($this->logDirectoryAndFilename, 'at');
		if ($fh) {
			fputs($fh, $output);
			fclose($fh);
		}
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
					$output .= str_repeat(' ',$spaces) . $k . ' => array (' . chr(10) . $this->getFormattedVarDump($v, $spaces+3) . str_repeat (' ', $spaces) . ')' . chr(10);
				} else {
					if (is_object($v)) {
						$output .= str_repeat(' ', $spaces) . $k . ' => object: ' . get_class($v) . chr(10);
					} else {
						$output .= str_repeat(' ',$spaces) . $k . ' => ' . $v . chr(10);
					}
				}
			}
		} else {
			if (is_object($var)) {
				$output .= str_repeat(' ', $spaces) . ' [ OBJECT: ' . \F3\PHP6\Functions::strtoupper(get_class($var)) . ' ]:' . chr(10);
				if (is_array(get_object_vars ($var))) {
					foreach (get_object_vars ($var) as $objVarName => $objVarValue) {
						if (is_array($objVarValue) || is_object($objVarValue)) {
							$output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . chr(10);
							$output .= $this->getFormattedVarDump($objVarValue, $spaces+3);
						} else {
							$output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . $objVarValue . chr(10);
						}
					}
				}
				$output .= chr(10);
			} else {
				$output .= str_repeat(' ', $spaces) . '=> ' . $var . chr(10);
			}
		}
		return $output;
	}
}
?>