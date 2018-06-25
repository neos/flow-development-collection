<?php
namespace Neos\Flow\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Http\Response;

/**
 * A quite exception handler which catches but ignores any exception.
 *
 * @Flow\Scope("singleton")
 */
class ProductionExceptionHandler extends AbstractExceptionHandler
{
    /**
     * Echoes an exception for the web.
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
        $referenceCode = ($exception instanceof FlowException) ? $exception->getReferenceCode() : null;
        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %s %s', $statusCode, $statusMessage));
        }

        try {
            if (isset($this->renderingOptions['templatePathAndFilename'])) {
                try {
                    echo $this->buildView($exception, $this->renderingOptions)->render();
                } catch (\Throwable $throwable) {
                    $this->renderStatically($statusCode, $throwable);
                } catch (\Exception $exception) {
                    $this->renderStatically($statusCode, $exception);
                }
            } else {
                echo $this->renderStatically($statusCode, $referenceCode);
            }
        } catch (\Exception $innerException) {
            $this->systemLogger->logException($innerException);
        }
    }

    /**
     * Returns the statically rendered exception message
     *
     * @param integer $statusCode
     * @param string $referenceCode
     * @return string
     */
    protected function renderStatically($statusCode, $referenceCode)
    {
        $statusMessage = Response::getStatusMessageByCode($statusCode);
        $referenceCodeMessage = ($referenceCode !== null) ? '<p>When contacting the maintainer of this application please mention the following reference code:<br /><br />' . $referenceCode . '</p>' : '';

        return '<!DOCTYPE html>
			<html>
				<head>
					<meta charset="UTF-8">
					<title>' . $statusCode . ' ' . $statusMessage . '</title>
					<style type="text/css">
						body {
							font-family: Helvetica, Arial, sans-serif;
							margin: 50px;
						}

						h1 {
						    color: #00ADEE;
							font-weight: normal;
						}
					</style>
				</head>
				<body>
                    <h1>' . $statusCode . ' ' . $statusMessage . '</h1>
                    <p>An internal error occurred.</p>
                    ' . $referenceCodeMessage . '
				</body>
			</html>';
    }
}
