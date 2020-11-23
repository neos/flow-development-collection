<?php
declare(strict_types=1);

namespace Neos\Flow\Mvc;

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
use Neos\Flow\Http\ServerRequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A dispatch middleware that runs the current HTTP request through the MVC stack
 */
class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Create an action request from stored route match values and dispatch to that
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $actionRequest = $request->getAttribute(ServerRequestAttributes::ACTION_REQUEST);
        if ($actionRequest === null) {
            throw new Exception('No ActionRequest was created before the DispatchMiddleware. Make sure you have the SecurityEntryPointMiddleware configured before dispatch.', 1605091292);
        }

        $actionResponse = new ActionResponse();
        $this->dispatcher->dispatch($actionRequest, $actionResponse);
        return $actionResponse->buildHttpResponse();
    }
}
