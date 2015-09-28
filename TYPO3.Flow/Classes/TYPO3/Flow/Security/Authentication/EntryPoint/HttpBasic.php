<?php
namespace TYPO3\Flow\Security\Authentication\EntryPoint;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;

/**
 * An authentication entry point, that sends an HTTP header to start HTTP Basic authentication.
 */
class HttpBasic extends AbstractEntryPoint
{
    /**
     * Starts the authentication: Send HTTP header
     *
     * @param \TYPO3\Flow\Http\Request $request The current request
     * @param \TYPO3\Flow\Http\Response $response The current response
     * @return void
     */
    public function startAuthentication(Request $request, Response $response)
    {
        $response->setStatus(401);
        $response->setHeader('WWW-Authenticate', 'Basic realm="' . (isset($this->options['realm']) ? $this->options['realm'] : sha1(FLOW_PATH_ROOT)) . '"');
        $response->setContent('Authorization required');
    }
}
