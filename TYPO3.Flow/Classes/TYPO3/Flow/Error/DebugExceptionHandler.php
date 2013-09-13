<?php
namespace TYPO3\Flow\Error;

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
use TYPO3\Flow\Http\Response;

/**
 * A basic but solid exception handler which catches everything which
 * falls through the other exception handlers and provides useful debugging
 * information.
 *
 * @Flow\Scope("singleton")
 */
class DebugExceptionHandler extends AbstractExceptionHandler {

	/**
	 * Formats and echoes the exception as XHTML.
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 */
	protected function echoExceptionWeb(\Exception $exception) {
		$statusCode = 500;
		if ($exception instanceof \TYPO3\Flow\Exception) {
			$statusCode = $exception->getStatusCode();
		}
		$statusMessage = Response::getStatusMessageByCode($statusCode);
		if (!headers_sent()) {
			header(sprintf('HTTP/1.1 %s %s', $statusCode, $statusMessage));
		}

		$renderingOptions = $this->resolveCustomRenderingOptions($exception);
		if (isset($renderingOptions['templatePathAndFilename'])) {
			echo $this->buildCustomFluidView($exception, $renderingOptions)->render();
		} else {
			echo $this->renderStatically($statusCode, $exception);
		}
	}

	/**
	 * Returns the statically rendered exception message
	 *
	 * @param integer $statusCode
	 * @param \Exception $exception
	 * @return void
	 */
	protected function renderStatically($statusCode, $exception) {
		$statusMessage = Response::getStatusMessageByCode($statusCode);
		$exceptionHeader = '';
		while (TRUE) {
			$pathPosition = strpos($exception->getFile(), 'Packages/');
			$filePathAndName = ($pathPosition !== FALSE) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();
			$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

			$moreInformationLink = ($exceptionCodeNumber != '') ? '<p><a href="http://typo3.org/go/exception/' . $exception->getCode() . '">More information</a></p>' : '';
			$createIssueLink = $this->getCreateIssueLink($exception);
			$exceptionMessageParts = $this->splitExceptionMessage($exception->getMessage());

			$exceptionHeader .= '<h2 class="ExceptionSubject">' . $exceptionCodeNumber . htmlspecialchars($exceptionMessageParts['subject']) . '</h2>';
			if ($exceptionMessageParts['body'] !== '') {
				$exceptionHeader .= '<p class="ExceptionBody">' . nl2br(htmlspecialchars($exceptionMessageParts['body'])) . '</p>';
			}
			$exceptionHeader .= $moreInformationLink . '
				<span class="ExceptionProperty">' . get_class($exception) . '</span> thrown in file<br />
				<span class="ExceptionProperty">' . $filePathAndName . '</span> in line
				<span class="ExceptionProperty">' . $exception->getLine() . '</span>.<br />';
			if ($exception instanceof \TYPO3\Flow\Exception) {
				$exceptionHeader .= '<span class="ExceptionProperty">Reference code: ' . $exception->getReferenceCode() . '</span><br />';
			}
			if ($exception->getPrevious() === NULL) {
				$exceptionHeader .= '<br /><a href="' . $createIssueLink . '">Go to the FORGE issue tracker and report the issue</a> - <strong>if you think it is a bug!</strong><br />';
				break;
			} else {
				$exceptionHeader .= '<br /><div style="width: 100%; background-color: #515151; color: white; padding: 2px; margin: 0 0 6px 0;">Nested Exception</div>';
				$exception = $exception->getPrevious();
			}
		}

		$backtraceCode = Debugger::getBacktraceCode($exception->getTrace());

		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
				"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
			<head>
				<title>' . $statusCode . ' ' . $statusMessage . '</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<style>
					.ExceptionSubject {
						margin: 0;
						padding: 0;
						font-size: 15px;
						color: #BE0027;
					}
					.ExceptionBody {
						padding: 10px;
						margin: 10px;
						color: black;
						background: #DDD;
					}
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
			</head>
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
				<div style="width: 100%; background-color: #515151; color: white; padding: 2px; margin: 0 0 6px 0;">Uncaught Exception in Flow</div>
				<div style="width: 100%; padding: 2px; margin: 0 0 6px 0;">
					' . $exceptionHeader . '
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
	 */
	protected function echoExceptionCli(\Exception $exception) {
		$pathPosition = strpos($exception->getFile(), 'Packages/');
		$filePathAndName = ($pathPosition !== FALSE) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();

		$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

		echo PHP_EOL . 'Uncaught Exception in Flow ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
		echo 'thrown in file ' . $filePathAndName . PHP_EOL;
		echo 'in line ' . $exception->getLine() . PHP_EOL;
		if ($exception instanceof \TYPO3\Flow\Exception) {
			echo 'Reference code: ' . $exception->getReferenceCode() . PHP_EOL;
		}

		$indent = '  ';
		while (($exception = $exception->getPrevious()) !== NULL) {
			echo PHP_EOL . $indent . 'Nested exception:' . PHP_EOL;
			$pathPosition = strpos($exception->getFile(), 'Packages/');
			$filePathAndName = ($pathPosition !== FALSE) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();

			$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

			echo PHP_EOL . $indent . 'Uncaught Exception in Flow ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
			echo $indent . 'thrown in file ' . $filePathAndName . PHP_EOL;
			echo $indent . 'in line ' . $exception->getLine() . PHP_EOL;
			if ($exception instanceof \TYPO3\Flow\Exception) {
				echo 'Reference code: ' . $exception->getReferenceCode() . PHP_EOL;
			}

			$indent .= '  ';
		}

		if (function_exists('xdebug_get_function_stack')) {
			$backtraceSteps = xdebug_get_function_stack();
		} else {
			$backtraceSteps = debug_backtrace();
		}

		for ($index = 0; $index < count($backtraceSteps); $index ++) {
			echo PHP_EOL . '#' . $index . ' ';
			if (isset($backtraceSteps[$index]['class'])) {
				echo $backtraceSteps[$index]['class'];
			}
			if (isset($backtraceSteps[$index]['function'])) {
				echo '::' . $backtraceSteps[$index]['function'] . '()';
			}
			echo PHP_EOL;
			if (isset($backtraceSteps[$index]['file'])) {
				echo '   ' . $backtraceSteps[$index]['file'] . (isset($backtraceSteps[$index]['line']) ? ':' . $backtraceSteps[$index]['line'] : '') . PHP_EOL;
			}
		}

		echo PHP_EOL;
		exit(1);
	}

	/**
	 * Returns a link pointing to Forge to create a new issue.
	 *
	 * @param \Exception $exception
	 * @return string
	 */
	protected function getCreateIssueLink(\Exception $exception) {
		$filename = basename($exception->getFile());
		return 'http://forge.typo3.org/projects/package-typo3-flow/issues/new?issue[subject]=' .
			urlencode (get_class($exception) . ' thrown in file ' . $filename) .
			'&issue[description]=' .
			urlencode (
				$exception->getMessage() . chr(10) .
				strip_tags(
					str_replace(array('<br />', '</pre>'), chr(10), Debugger::getBacktraceCode($exception->getTrace(), FALSE))
				) .
				chr(10) . 'Please include more helpful information!'
			) .
			'&issue[category_id]=554&issue[priority_id]=7';
	}

	/**
	 * Splits the given string into subject and body according to following rules:
	 * - If the string is empty or does not contain more than one sentence nor line breaks, the subject will be equal to the string and body will be an empty string
	 * - Otherwise the subject is everything until the first line break or end of sentence, the body contains the rest
	 *
	 * @param string $exceptionMessage
	 * @return array in the format array('subject' => '<subject>', 'body' => '<body>');
	 */
	protected function splitExceptionMessage($exceptionMessage) {
		$subject = '';
		$body = '';
		$pattern = '/
			(?<=                # Begin positive lookbehind.
			  [.!?]\s           # Either an end of sentence punct,
			| \n                # or line break
			)
			(?<!                # Begin negative lookbehind.
			  i\.E\.\s          # Skip "i.E."
			)                   # End negative lookbehind.
			/ix';
		$sentences = preg_split($pattern, $exceptionMessage, 2, PREG_SPLIT_NO_EMPTY);
		if (!isset($sentences[1])) {
			$subject = $exceptionMessage;
		} else {
			$subject = trim($sentences[0]);
			$body = trim($sentences[1]);
		}
		return array(
			'subject' => $subject,
			'body' => $body
		);
	}
}
?>