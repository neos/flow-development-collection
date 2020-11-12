<?php
namespace Neos\Flow\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @return ServerRequestInterface
     */
    public function getHttpRequest();

    /**
     * Returns the HTTP response corresponding to the currently handled request
     *
     * @return ResponseInterface|null
     * @deprecated Don't depend on this method. The HTTP response only exists after the innermost middleware (dispatch) is done. For that stage use a middleware instead.
     */
    public function getHttpResponse();
}
