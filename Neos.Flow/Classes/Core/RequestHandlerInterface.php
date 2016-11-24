<?php
namespace Neos\Flow\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The interface for a request handler
 *
 * @api
 */
interface RequestHandlerInterface
{
    /**
     * Handles a raw request
     *
     * @return void
     * @api
     */
    public function handleRequest();

    /**
     * Checks if the request handler can handle the current request.
     *
     * @return mixed TRUE or an integer > 0 if it can handle the request, otherwise FALSE or an integer < 0
     * @api
     */
    public function canHandleRequest();

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request. An integer > 0 means "I want to handle this request" where
     * "100" is default. "0" means "I am a fallback solution".
     *
     * @return integer The priority of the request handler
     * @api
     */
    public function getPriority();
}
