<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface for a Request Engine which can be used by a HTTP Client implementation
 * for sending requests and returning responses.
 */
interface RequestEngineInterface
{
    /**
     * Sends the given request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws Http\Exception
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
