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
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Psr\Http\Message\ResponseInterface;

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
     * @param \Throwable $exception
     * @return void
     */
    protected function echoExceptionWeb($exception)
    {
        $statusCode = ($exception instanceof WithHttpStatusInterface) ? $exception->getStatusCode() : 500;
        $statusMessage = ResponseInformationHelper::getStatusMessageByCode($statusCode);
        $referenceCode = ($exception instanceof WithReferenceCodeInterface) ? $exception->getReferenceCode() : null;
        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %s %s', $statusCode, $statusMessage));
        }

        try {
            if ($this->useCustomErrorView()) {
                try {
                    $stream = $this->buildView($exception, $this->renderingOptions)->render();
                    if ($stream instanceof ResponseInterface) {
                        /**
                         * The http status code will already be sent, and we are only currently interested in the content stream
                         * Thus, we unwrap the repose here:
                         */
                        $stream = $stream->getBody();
                    }
                    $resourceOrString = $stream->detach() ?: $stream->getContents();
                    if (is_resource($resourceOrString)) {
                        fpassthru($resourceOrString);
                        fclose($resourceOrString);
                    } else {
                        echo $resourceOrString;
                    }
                } catch (\Throwable $throwable) {
                    $this->renderStatically($statusCode, $throwable);
                }
            } else {
                echo $this->renderStatically($statusCode, $referenceCode);
            }
        } catch (\Exception $innerException) {
            $message = $this->throwableStorage->logThrowable($innerException);
            $this->logger->critical($message);
        }
    }

    /**
     * Returns the statically rendered exception message
     *
     * @param integer $statusCode
     * @param string $referenceCode
     * @return string
     */
    protected function renderStatically(int $statusCode, ?string $referenceCode): string
    {
        $statusMessage = ResponseInformationHelper::getStatusMessageByCode($statusCode);
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
