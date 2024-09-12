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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\InvalidActionVisibilityException;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\ViewNotFoundException;
use Neos\Flow\Property\Exception;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;
use Psr\Http\Message\ResponseInterface;

/**
 * This is the base class for all widget controllers.
 * Basically, it is an ActionController, and it additionally
 * has $this->widgetConfiguration set to the Configuration of the current Widget.
 *
 * @api
 */
abstract class AbstractWidgetController extends ActionController
{
    /**
     * Configuration for this widget.
     *
     * @var array
     * @api
     */
    protected $widgetConfiguration;

    /**
     * Handles a request. The result output is returned by altering the given response.
     *
     * @param ActionRequest $request The request object
     * @return ResponseInterface The response, created by this handler
     * @throws WidgetContextNotFoundException
     * @throws InvalidActionVisibilityException
     * @throws InvalidArgumentTypeException
     * @throws NoSuchActionException
     * @throws NoSuchArgumentException
     * @throws StopActionException
     * @throws ForwardException
     * @throws ViewNotFoundException
     * @throws Exception
     * @throws \Neos\Flow\Security\Exception
     * @api
     */
    public function processRequest(ActionRequest $request): ResponseInterface
    {
        /** @var WidgetContext $widgetContext */
        $widgetContext = $request->getInternalArgument('__widgetContext');
        if (!$widgetContext instanceof WidgetContext) {
            throw new WidgetContextNotFoundException('The widget context could not be found in the request.', 1307450180);
        }
        $this->widgetConfiguration = $widgetContext->getWidgetConfiguration();
        return parent::processRequest($request);
    }
}
