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

use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * An authentication entry point, that sends an HTTP header to start HTTP Basic authentication.
 */
class HttpBasic extends AbstractEntryPoint
{
    /**
     * Starts the authentication: Send HTTP header
     *
     * @param ServerRequestInterface $request The current request
     * @param ResponseInterface $response The current response
     * @return ResponseInterface
     */
    public function startAuthentication(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response->withStatus(401)
            ->withHeader('WWW-Authenticate', 'Basic realm="' . ($this->options['realm'] ?? sha1(FLOW_PATH_ROOT)) . '"')
            ->withBody(stream_for('Authorization required'));
    }
}
