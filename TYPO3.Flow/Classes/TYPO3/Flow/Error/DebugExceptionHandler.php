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
use TYPO3\Flow\Exception as FlowException;
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
        if ($exception instanceof FlowException) {
            $statusCode = $exception->getStatusCode();
        }
        $statusMessage = Response::getStatusMessageByCode($statusCode);
        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %s %s', $statusCode, $statusMessage));
        }

        if (isset($this->renderingOptions['templatePathAndFilename'])) {
            try {
                echo $this->buildCustomFluidView($exception, $this->renderingOptions)->render();
            } catch (\Throwable $throwable) {
                $this->renderStatically($statusCode, $throwable);
            } catch (\Exception $exception) {
                $this->renderStatically($statusCode, $exception);
            }
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

            $exceptionMessageParts = $this->splitExceptionMessage($exception->getMessage());

            $exceptionHeader .= '<h2 class="ExceptionSubject">' . $exceptionCodeNumber . htmlspecialchars($exceptionMessageParts['subject']) . '</h2>';
            if ($exceptionMessageParts['body'] !== '') {
                $exceptionHeader .= '<p class="ExceptionBody">' . nl2br(htmlspecialchars($exceptionMessageParts['body'])) . '</p>';
            }
            $exceptionHeader .= '
				<span class="ExceptionProperty">' . get_class($exception) . '</span> thrown in file<br />
				<span class="ExceptionProperty">' . $filePathAndName . '</span> in line
				<span class="ExceptionProperty">' . $exception->getLine() . '</span>.<br />';
            if ($exception instanceof FlowException) {
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
}
