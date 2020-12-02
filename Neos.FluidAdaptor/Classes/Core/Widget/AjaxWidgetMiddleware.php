<?php
declare(strict_types=1);

namespace Neos\FluidAdaptor\Core\Widget;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Utility\Arrays;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A HTTP middleware specifically for Ajax widgets
 * It's task is to interrupt the default dispatching as soon as possible if the current request is an AJAX request
 * triggered by a Fluid widget (e.g. contains the arguments "__widgetId" or "__widgetContext").
 */
class AjaxWidgetMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var ActionRequestFactory
     */
    protected $actionRequestFactory;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @Flow\Inject
     * @var AjaxWidgetContextHolder
     */
    protected $ajaxWidgetContextHolder;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Check if the current request contains a widget context.
     * If so dispatch it directly, otherwise continue with the next HTTP component.
     *
     * @param ServerRequestInterface $httpRequest
     * @param RequestHandlerInterface $next
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $httpRequest, RequestHandlerInterface $next): ResponseInterface
    {
        $widgetContext = $this->extractWidgetContext($httpRequest);
        if ($widgetContext === null) {
            return $next->handle($httpRequest);
        }

        $actionRequest = $this->actionRequestFactory->createActionRequest($httpRequest, ['__widgetContext' => $widgetContext]);
        $actionRequest->setControllerObjectName($widgetContext->getControllerObjectName());
        $this->securityContext->setRequest($actionRequest);

        $actionResponse = new ActionResponse();

        $this->dispatcher->dispatch($actionRequest, $actionResponse);

        return $actionResponse->buildHttpResponse();
    }

    /**
     * Extracts the WidgetContext from the given $httpRequest.
     * If the request contains an argument "__widgetId" the context is fetched from the session (AjaxWidgetContextHolder).
     * Otherwise the argument "__widgetContext" is expected to contain the serialized WidgetContext (protected by a HMAC suffix)
     *
     * @param ServerRequestInterface $httpRequest
     * @return WidgetContext
     * @throws Exception\WidgetContextNotFoundException
     * @throws \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     * @throws \Neos\Flow\Security\Exception\InvalidHashException
     */
    protected function extractWidgetContext(ServerRequestInterface $httpRequest):? WidgetContext
    {
        $arguments = $httpRequest->getQueryParams();
        $parsedBody  = $httpRequest->getParsedBody();
        if (is_array($parsedBody)) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $parsedBody);
        }
        if (isset($arguments['__widgetId'])) {
            return $this->ajaxWidgetContextHolder->get($arguments['__widgetId']);
        }

        if (isset($arguments['__widgetContext'])) {
            $serializedWidgetContextWithHmac = $arguments['__widgetContext'];
            $serializedWidgetContext = $this->hashService->validateAndStripHmac($serializedWidgetContextWithHmac);
            return unserialize(base64_decode($serializedWidgetContext));
        }

        return null;
    }
}
