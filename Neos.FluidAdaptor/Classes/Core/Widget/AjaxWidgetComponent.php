<?php
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
use Neos\Flow\Http\Component\Exception as ComponentException;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Security\Cryptography\HashService;

/**
 * A HTTP component specifically for Ajax widgets
 * It's task is to interrupt the default dispatching as soon as possible if the current request is an AJAX request
 * triggered by a Fluid widget (e.g. contains the arguments "__widgetId" or "__widgetContext").
 */
class AjaxWidgetComponent extends DispatchComponent
{
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
     * Check if the current request contains a widget context.
     * If so dispatch it directly, otherwise continue with the next HTTP component.
     *
     * @param ComponentContext $componentContext
     * @return void
     * @throws ComponentException
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();
        $widgetContext = $this->extractWidgetContext($httpRequest);
        if ($widgetContext === null) {
            return;
        }
        /** @var $actionRequest ActionRequest */
        $actionRequest = $this->objectManager->get(\Neos\Flow\Mvc\ActionRequest::class, $httpRequest);
        $actionRequest->setArguments($this->mergeArguments($httpRequest, array()));
        $actionRequest->setArgument('__widgetContext', $widgetContext);
        $actionRequest->setControllerObjectName($widgetContext->getControllerObjectName());
        $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

        $this->securityContext->setRequest($actionRequest);

        $this->dispatcher->dispatch($actionRequest, $componentContext->getHttpResponse());
        // stop processing the current component chain
        $componentContext->setParameter(\Neos\Flow\Http\Component\ComponentChain::class, 'cancel', true);
    }

    /**
     * Extracts the WidgetContext from the given $httpRequest.
     * If the request contains an argument "__widgetId" the context is fetched from the session (AjaxWidgetContextHolder).
     * Otherwise the argument "__widgetContext" is expected to contain the serialized WidgetContext (protected by a HMAC suffix)
     *
     * @param Request $httpRequest
     * @return WidgetContext
     */
    protected function extractWidgetContext(Request $httpRequest)
    {
        if ($httpRequest->hasArgument('__widgetId')) {
            return $this->ajaxWidgetContextHolder->get($httpRequest->getArgument('__widgetId'));
        } elseif ($httpRequest->hasArgument('__widgetContext')) {
            $serializedWidgetContextWithHmac = $httpRequest->getArgument('__widgetContext');
            $serializedWidgetContext = $this->hashService->validateAndStripHmac($serializedWidgetContextWithHmac);
            return unserialize(base64_decode($serializedWidgetContext));
        }
        return null;
    }
}
