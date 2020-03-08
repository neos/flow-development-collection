<?php
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
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\Security\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A MVC dispatch middleware
 *
 * Note, this is a final middleware that will not delegate further.
 */
class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @Flow\Inject(lazy=false)
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject(lazy=false)
     * @var ActionRequestFactory
     */
    protected $actionRequestFactory;

    /**
     * @Flow\Inject(lazy=false)
     * @var ComponentContext
     */
    protected $componentContext;

    /**
     * Create an action request from stored route match values and dispatch to that
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Controller\Exception\InvalidControllerException
     * @throws Exception\InfiniteLoopException
     * @throws Exception\InvalidActionNameException
     * @throws Exception\InvalidArgumentNameException
     * @throws Exception\InvalidArgumentTypeException
     * @throws Exception\InvalidControllerNameException
     * @throws \Neos\Flow\Configuration\Exception\NoSuchOptionException
     * @throws \Neos\Flow\Security\Exception\AccessDeniedException
     * @throws \Neos\Flow\Security\Exception\AuthenticationRequiredException
     * @throws \Neos\Flow\Security\Exception\MissingConfigurationException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routingMatchResults = $this->componentContext->getParameter(RoutingComponent::class, 'matchResults');
        $actionRequest = $this->actionRequestFactory->createActionRequest($request, $routingMatchResults ?? []);
        $this->securityContext->setRequest($actionRequest);
        // TODO: Only for b/c reasons. Remove with Flow 7
        $this->componentContext->setParameter(DispatchComponent::class, 'actionRequest', $actionRequest);

        $actionResponse = new ActionResponse();
        $this->dispatcher->dispatch($actionRequest, $actionResponse);
        // TODO: This needs to be `applyToResponse` once the componentChain is removed
        $actionResponse->mergeIntoComponentContext($this->componentContext);

        $possibleResponse = $this->componentContext->getParameter(ReplaceHttpResponseComponent::class, ReplaceHttpResponseComponent::PARAMETER_RESPONSE);
        if ($possibleResponse instanceof ResponseInterface) {
            $this->componentContext->replaceHttpResponse($possibleResponse);
        }
        return $this->componentContext->getHttpResponse();
    }
}
