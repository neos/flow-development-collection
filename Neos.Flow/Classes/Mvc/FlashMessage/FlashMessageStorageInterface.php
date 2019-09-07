<?php
namespace Neos\Flow\Mvc\FlashMessage;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\ServerRequestInterface as HttpRequestInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

/**
 * Contract for FlashMessage storages
 */
interface FlashMessageStorageInterface
{
    /**
     * Constructs the FlashMessage storage and, optionally, sets some implementation specific options
     * Note: This method is commented because interfaces are not meant to define constructors - also a custom constructor is not required
     *
     * @param array $options The FlashMessage storage options
     * @api
     */
    // public function __construct(array $options = []);

    /**
     * @param HttpRequestInterface $request The current HTTP request for storages that persist the FlashMessages via HTTP
     * @return FlashMessageContainer
     */
    public function load(HttpRequestInterface $request): FlashMessageContainer;

    /**
     * @param HttpResponseInterface $response The current HTTP response for storages that persist the FlashMessages via HTTP
     * @return HttpResponseInterface
     */
    public function persist(HttpResponseInterface $response): HttpResponseInterface;
}
