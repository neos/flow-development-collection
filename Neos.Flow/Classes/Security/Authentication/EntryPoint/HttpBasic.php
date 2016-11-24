<?php
namespace Neos\Flow\Security\Authentication\EntryPoint;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;

/**
 * An authentication entry point, that sends an HTTP header to start HTTP Basic authentication.
 */
class HttpBasic extends AbstractEntryPoint
{
    /**
     * Starts the authentication: Send HTTP header
     *
     * @param Request $request The current request
     * @param Response $response The current response
     * @return void
     */
    public function startAuthentication(Request $request, Response $response)
    {
        $response->setStatus(401);
        $response->setHeader('WWW-Authenticate', 'Basic realm="' . (isset($this->options['realm']) ? $this->options['realm'] : sha1(FLOW_PATH_ROOT)) . '"');
        $response->setContent('Authorization required');
    }
}
