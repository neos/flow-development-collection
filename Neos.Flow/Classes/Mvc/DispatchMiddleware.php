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
use Neos\Flow\Http\Middleware\NotFoundMiddleware;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Controller\Exception\InvalidControllerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A dispatch middleware that runs the current HTTP request through the MVC stack
 */
class DispatchMiddleware implements MiddlewareInterface
{
    public const ABSTAIN_STATUSCODE = 'X-Flow-Abstain-StatusCode';

    public const ABSTAIN_DESCRIPTION = 'X-Flow-Abstain-Description';

    public const ABSTAIN_DETAILS = 'X-Flow-Abstain-Details';

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
        /** @var ActionRequest $actionRequest */
        $actionRequest = $request->getAttribute(ServerRequestAttributes::ACTION_REQUEST);
        if ($actionRequest === null) {
             return $next->handle(
                $request
                    ->withAttribute(NotFoundMiddleware::DETAILS, 'No ActionRequest was created before the DispatchMiddleware. Make sure you have the SecurityEntryPointMiddleware configured before dispatch.')
                    ->withAttribute(NotFoundMiddleware::REFERENCE_CODE, 1605091292)
            );
        }

        try {
            $actionResponse = new ActionResponse();
            $this->dispatcher->dispatch($actionRequest, $actionResponse);
            $httpResponse = $actionResponse->buildHttpResponse();
        } catch (InvalidControllerException $e) {
            return $next->handle(
                $request
                    ->withAttribute(NotFoundMiddleware::DETAILS, $e->getMessage())
                    ->withAttribute(NotFoundMiddleware::REFERENCE_CODE, $e->getReferenceCode())
            );
        }

        if ($httpResponse->hasHeader(self::ABSTAIN_STATUSCODE)) {
            return $next->handle(
                $request
                    ->withAttribute(NotFoundMiddleware::STATUS_CODE, $httpResponse->getHeaderLine(self::ABSTAIN_STATUSCODE))
                    ->withAttribute(NotFoundMiddleware::DESCRIPTION, $httpResponse->getHeaderLine(self::ABSTAIN_DESCRIPTION))
                    ->withAttribute(NotFoundMiddleware::DETAILS, $httpResponse->getHeaderLine(self::ABSTAIN_DETAILS))
            );
        }

        return $httpResponse;
    }
}
