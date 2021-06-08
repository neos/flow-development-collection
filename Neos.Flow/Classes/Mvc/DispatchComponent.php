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

/**
 * A dispatch component
 */
class DispatchComponent implements ComponentInterface
{
    /**
     * @Flow\Inject
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
        $actionResponse = new ActionResponse();
        $this->dispatcher->dispatch($actionRequest, $actionResponse);
        $actionResponse->mergeIntoComponentContext($componentContext);
    }
}
