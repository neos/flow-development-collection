<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Error;

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
 * A basic but solid exception handler which catches everything which
 * falls through the other exception handlers and provides useful debugging
 * information.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DebugExceptionHandler extends \F3\FLOW3\Error\AbstractExceptionHandler {

	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		set_exception_handler(array($this, 'handleException'));
	}

	/**
	 * Displays the given exception
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleException(\Exception $exception) {
		parent::handleException($exception);

		switch (PHP_SAPI) {
			case 'cli' :
				$this->echoExceptionCLI($exception);
				break;
			default :
				$this->echoExceptionWeb($exception);
		}
	}

	/**
	 * Formats and echoes the exception as XHTML.
	 *
	 * @param  \Exception $exception The exception object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function echoExceptionWeb(\Exception $exception) {
		if (!headers_sent()) {
			header("HTTP/1.1 500 Internal Server Error");
		}

		$pathPosition = strpos($exception->getFile(), 'Packages/');
		$filePathAndName = ($pathPosition !== FALSE) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();

		$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
		$moreInformationLink = ($exceptionCodeNumber != '') ? '(<a href="http://typo3.org/go/exception/' . $exception->getCode() . '">More information</a>)' : '';
		$createIssueLink = $this->getCreateIssueLink($exception);
		$backtraceCode = $this->getBacktraceCode($exception->getTrace());

		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
				"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
			<head>
				<title>FLOW3 Exception</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			</head>
			<style>
				.ExceptionProperty {
					color: #101010;
				}
				pre {
					margin: 0;
					font-size: 11px;
					color: #515151;
					background-color: #D0D0D0;
					padding-left: 30px;
				}
			</style>
			<div style="
					position: absolute;
					left: 10px;
					background-color: #B9B9B9;
					outline: 1px solid #515151;
					color: #515151;
					font-family: Arial, Helvetica, sans-serif;
					font-size: 12px;
					margin: 10px;
					padding: 0;
				">
				<div style="width: 100%; background-color: #515151; color: white; padding: 2px; margin: 0 0 6px 0;">Uncaught Exception in FLOW3</div>
				<div style="width: 100%; padding: 2px; margin: 0 0 6px 0;">
					<strong style="color: #BE0027;">' . $exceptionCodeNumber . htmlspecialchars($exception->getMessage()) . '</strong> ' . $moreInformationLink . '<br />
					<br />
					<span class="ExceptionProperty">' . get_class($exception) . '</span> thrown in file<br />
					<span class="ExceptionProperty">' . $filePathAndName . '</span> in line
					<span class="ExceptionProperty">' . $exception->getLine() . '</span>.<br />
					<br />
					<a href="' . $createIssueLink . '">Go to the FORGE issue tracker and report the issue</a> - <strong>if you think it is a bug!</strong><br />
					<br />
					' . $backtraceCode . '
				</div>
			</div>
		';
	}

	/**
	 * Formats and echoes the exception for the command line
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function echoExceptionCLI(\Exception $exception) {
		$pathPosition = strpos($exception->getFile(), 'Packages/');
		$filePathAndName = ($pathPosition !== FALSE) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();

		$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

		echo PHP_EOL . 'Uncaught Exception in FLOW3 ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
		echo 'thrown in file ' . $filePathAndName . PHP_EOL;
		echo 'in line ' . $exception->getLine() . PHP_EOL . PHP_EOL;
	}

	/**
	 * Renders some backtrace
	 *
	 * @param array $trace The trace
	 * @return string Backtrace information
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getBacktraceCode(array $trace, $includeCode = TRUE) {
		$backtraceCode = '';
		if (count($trace)) {
			foreach ($trace as $index => $step) {
				$class = isset($step['class']) ? $step['class'] . '<span style="color:white;">::</span>' : '';

				$arguments = '';
				if (isset($step['args']) && is_array($step['args'])) {
					foreach ($step['args'] as $argument) {
						$arguments .= (strlen($arguments) === 0) ? '' : '<span style="color:white;">,</span> ';
						if (is_object($argument)) {
							$arguments .= '<span style="color:#FF8700;"><em>' . get_class($argument) . '</em></span>';
						} elseif (is_string($argument)) {
							$preparedArgument = (strlen($argument) < 100) ? $argument : substr($argument, 0, 50) . '…' . substr($argument, -50);
							$preparedArgument = htmlspecialchars($preparedArgument);
							$preparedArgument = str_replace("…", '<span style="color:white;">…</span>', $preparedArgument);
							$preparedArgument = str_replace("\n", '<span style="color:white;">⏎</span>', $preparedArgument);
							$arguments .= '"<span style="color:#FF8700;" title="' . htmlspecialchars($argument) . '">' . $preparedArgument . '</span>"';
						} elseif (is_numeric($argument)) {
							$arguments .= '<span style="color:#FF8700;">' . (string)$argument . '</span>';
						} else {
							$arguments .= '<span style="color:#FF8700;"><em>' . gettype($argument) . '</em></span>';
						}
					}
				}

				$backtraceCode .= '<pre style="color:#69A550; background-color: #414141; padding: 4px 2px 4px 2px;">';
				$backtraceCode .= '<span style="color:white;">' . (count($trace) - $index) . '</span> ' . $class . $step['function'] . '<span style="color:white;">(' . $arguments . ')</span>';
				$backtraceCode .= '</pre>';

				if (isset($step['file']) && $includeCode) {
					$backtraceCode .= $this->getCodeSnippet($step['file'], $step['line']) . '<br />';
				}
			}
		}

		return $backtraceCode;
	}

	/**
	 * Returns a code snippet from the specified file.
	 *
	 * @param string $filePathAndName Absolute path and file name of the PHP file
	 * @param integer $lineNumber Line number defining the center of the code snippet
	 * @return string The code snippet
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getCodeSnippet($filePathAndName, $lineNumber) {
		$pathPosition = strpos($filePathAndName, 'Packages/');
		$codeSnippet = '<br />';
		if (@file_exists($filePathAndName)) {
			$phpFile = @file($filePathAndName);
			if (is_array($phpFile)) {
				$startLine = ($lineNumber > 2) ? ($lineNumber - 2) : 1;
				$endLine = ($lineNumber < (count($phpFile) - 2)) ? ($lineNumber + 3) : count($phpFile) + 1;
				if ($endLine > $startLine) {
					if ($pathPosition !== FALSE) {
						$codeSnippet = '<br /><span style="font-size:10px;">' . substr($filePathAndName, $pathPosition) . ':</span><br /><pre>';
					} else {
						$codeSnippet = '<br /><span style="font-size:10px;">' . $filePathAndName . ':</span><br /><pre>';
					}
					for ($line = $startLine; $line < $endLine; $line++) {
						$codeLine = str_replace("\t", ' ', $phpFile[$line-1]);

						if ($line === $lineNumber) {
							$codeSnippet .= '</pre><pre style="background-color: #F1F1F1; color: black;">';
						}
						$codeSnippet .= sprintf('%05d', $line) . ': ' . $codeLine;
						if ($line === $lineNumber) {
							$codeSnippet .= '</pre><pre>';
						}
					}
					$codeSnippet .= '</pre>';
				}
			}
		}
		return $codeSnippet;
	}

	/**
	 * Returns a link pointing to Forge to create a new issue.
	 *
	 * @param Exception $exception
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getCreateIssueLink(\Exception $exception) {
		$filename = basename($exception->getFile());
		return 'http://forge.typo3.org/projects/package-flow3/issues/new?issue[subject]='
			. urlencode(get_class($exception) . ' thrown in file ' . $filename)
			. '&issue[description]='
			. urlencode(
				$exception->getMessage() . chr(10)
				. strip_tags(
					str_replace(array('<br />', '</pre>'), chr(10), $this->getBacktraceCode($exception->getTrace(), FALSE))
				  )
				. chr(10) . 'Please include more helpful information!'
			  )
			. '&issue[category_id]=554&issue[priority_id]=7';
	}
}

?>