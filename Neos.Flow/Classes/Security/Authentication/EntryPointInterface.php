<?php
namespace Neos\Flow\Security\Authentication;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contract for an authentication entry point
 */
interface EntryPointInterface
{
    /**
     * Sets the options array
     *
     * @param array $options An array of configuration options
     * @return void
     */
    public function setOptions(array $options);

    /**
     * Returns the options array
     *
     * @return array An array of configuration options
     */
    public function getOptions();

    /**
     * Starts the authentication. (e.g. redirect to login page or send 401 HTTP header)
     *
     * @param ServerRequestInterface $request The current request
     * @param ResponseInterface $response The current response
     * @return ResponseInterface
     */
    public function startAuthentication(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
