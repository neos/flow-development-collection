<?php
namespace TYPO3\Flow\Http;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Core\RequestHandlerInterface;

/**
 * The interface for a request handler which handles and works with HTTP requests
 *
 * @api
 */
interface HttpRequestHandlerInterface extends RequestHandlerInterface
{
    /**
     * Returns the currently processed HTTP request
     *
     * @return \TYPO3\Flow\Http\Request
     * @api
     */
    public function getHttpRequest();

    /**
     * Returns the HTTP response corresponding to the currently handled request
     *
     * @return \TYPO3\Flow\Http\Response
     * @api
     */
    public function getHttpResponse();
}
