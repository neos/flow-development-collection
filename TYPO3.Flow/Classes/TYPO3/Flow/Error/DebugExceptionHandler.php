<?php
namespace TYPO3\Flow\Error;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Response;

/**
 * A basic but solid exception handler which catches everything which
 * falls through the other exception handlers and provides useful debugging
 * information.
 *
 * @Flow\Scope("singleton")
 */
class DebugExceptionHandler extends AbstractExceptionHandler
{
    /**
     * Formats and echoes the exception as XHTML.
     *
     * @param object $exception \Exception or \Throwable
     * @return void
     */
    protected function echoExceptionWeb($exception)
    {
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
            $this->renderStatically($statusCode, $exception);
        }
    }

    /**
     * Returns the statically rendered exception message
     *
     * @param integer $statusCode
     * @param object $exception \Exception or \Throwable
     * @return void
     */
    protected function renderStatically($statusCode, $exception)
    {
        $statusMessage = Response::getStatusMessageByCode($statusCode);
        $exceptionHeader = '';
        while (true) {
            $pathPosition = strpos($exception->getFile(), 'Packages/');
            $filePathAndName = ($pathPosition !== false) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();
            $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

            $moreInformationLink = ($exceptionCodeNumber != '') ? '<p><a href="http://typo3.org/go/exception/' . $exception->getCode() . '">More information</a></p>' : '';
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

            if ($exception->getPrevious() === null) {
                break;
            }

            $exceptionHeader .= '<br /><div style="width: 100%; background-color: #515151; color: white; padding: 2px; margin: 0 0 6px 0;">Nested Exception</div>';
            $exception = $exception->getPrevious();
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
    protected function echoExceptionCli(\Exception $exception)
    {
        $response = new \TYPO3\Flow\Cli\Response();

        $backtraceSteps = $exception->getTrace();
        $pathPosition = strpos($exception->getFile(), 'Packages/');
        $filePathAndName = ($pathPosition !== false) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();

        $exceptionMessage = PHP_EOL . '<b>Uncaught Exception: ' . get_class($exception) . '</b>' . PHP_EOL . PHP_EOL;
        $exceptionMessage .= '<b>Message</b>' . PHP_EOL;
        foreach (explode(chr(10), wordwrap($exception->getMessage(), 73)) as $messageLine) {
            $exceptionMessage .= '  ' . $messageLine . PHP_EOL;
        }

        $exceptionMessage .= PHP_EOL . '<b>More Information</b>' . PHP_EOL;
        if ($exception->getCode()) {
            $exceptionMessage .= '  Exception code ' . $exception->getCode() . PHP_EOL;
        }
        $exceptionMessage .= '  File           ' . $filePathAndName . ' line ' . $exception->getLine() . PHP_EOL;
        if ($exception instanceof \TYPO3\Flow\Exception) {
            $exceptionMessage .= '  Reference code ' . $exception->getReferenceCode() . PHP_EOL;
        }

        $indent = '  ';
        while (($exception = $exception->getPrevious()) !== null) {
            $exceptionMessage .= PHP_EOL . $indent . '<b>Nested exception: ' . get_class($exception) . '</b>' . PHP_EOL . PHP_EOL;
            $exceptionMessage .= $indent . '<b>Message</b>' . PHP_EOL;
            foreach (explode(chr(10), wordwrap($exception->getMessage(), 73)) as $messageLine) {
                $exceptionMessage .= $indent . '  ' . $messageLine . PHP_EOL;
            }

            $exceptionMessage .= PHP_EOL . $indent . '<b>More Information</b>' . PHP_EOL;
            if ($exception->getCode()) {
                $exceptionMessage .= $indent . '  Exception code ' . $exception->getCode() . PHP_EOL;
            }
            $exceptionMessage .= $indent . '  File           ' . $filePathAndName . ' line ' . $exception->getLine() . PHP_EOL;
            if ($exception instanceof \TYPO3\Flow\Exception) {
                $exceptionMessage .= $indent . '  Reference code ' . $exception->getReferenceCode() . PHP_EOL;
            }

            $indent .= '  ';
        }

        $exceptionMessage .= PHP_EOL . '<b>Stack trace</b>' . PHP_EOL;
        for ($index = 0; $index < count($backtraceSteps); $index ++) {
            $exceptionMessage .= PHP_EOL . '#' . $index . ' ';
            if (isset($backtraceSteps[$index]['class'])) {
                $exceptionMessage .= $backtraceSteps[$index]['class'];
            }
            if (isset($backtraceSteps[$index]['function'])) {
                $exceptionMessage .= '::' . $backtraceSteps[$index]['function'] . '()';
            }
            $exceptionMessage .= PHP_EOL;
            if (isset($backtraceSteps[$index]['file'])) {
                $exceptionMessage .= '   ' . $backtraceSteps[$index]['file'] . (isset($backtraceSteps[$index]['line']) ? ':' . $backtraceSteps[$index]['line'] : '') . PHP_EOL;
            }
        }
        $exceptionMessage .= PHP_EOL;

        $response->setContent($exceptionMessage);
        $response->send();
        exit(1);
    }

    /**
     * Splits the given string into subject and body according to following rules:
     * - If the string is empty or does not contain more than one sentence nor line breaks, the subject will be equal to the string and body will be an empty string
     * - Otherwise the subject is everything until the first line break or end of sentence, the body contains the rest
     *
     * @param string $exceptionMessage
     * @return array in the format array('subject' => '<subject>', 'body' => '<body>');
     */
    protected function splitExceptionMessage($exceptionMessage)
    {
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
