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
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Http\Component\SecurityEntryPointComponent;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;

/**
 * A dispatch component
 */
class DispatchComponent implements ComponentInterface
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Create an action request from stored route match values and dispatch to that
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $actionRequest = $componentContext->getParameter(DispatchComponent::class, 'actionRequest');
        $actionResponse = $this->prepareActionResponse($componentContext->getHttpResponse());
        try {
            $this->dispatcher->dispatch($actionRequest, $actionResponse);
        } catch (AuthenticationRequiredException $exception) {
            $componentContext->setParameter(
                SecurityEntryPointComponent::class,
                SecurityEntryPointComponent::AUTHENTICATION_EXCEPTION,
                $exception
            );
            return;
        }

        $actionReponseRenderer = new \Neos\Flow\Mvc\ActionResponseRenderer\IntoComponentContext($componentContext);
        $componentContext = $actionResponse->prepareRendering($actionReponseRenderer)->render();
//         TODO: This should change in next major when the action response is no longer a HTTP response for backward compatibility.
//        $componentContext->replaceHttpResponse($actionResponse);
    }

    /**
     * Prepares the ActionResponse to be dispatched
     *
     * TODO: Needs to be adapted for next major when we only deliver an action response inside the dispatch.
     *
     * @param \Psr\Http\Message\ResponseInterface $httpResponse
     * @return ActionResponse|\Psr\Http\Message\ResponseInterface
     */
    protected function prepareActionResponse(\Psr\Http\Message\ResponseInterface $httpResponse): ActionResponse
    {
        $rawResponse = implode("\r\n", ResponseInformationHelper::prepareHeaders($httpResponse));
        $rawResponse .= "\r\n\r\n";
        $rawResponse .= $httpResponse->getBody()->getContents();

        return ActionResponse::createFromRaw($rawResponse);
    }
}
