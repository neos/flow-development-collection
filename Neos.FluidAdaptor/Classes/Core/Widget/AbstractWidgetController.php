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

use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Mvc\ResponseInterface;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;

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
     * @param RequestInterface $request The request object
     * @param ResponseInterface $response The response, modified by this handler
     * @return void
     * @throws WidgetContextNotFoundException
     * @api
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        /** @var $request \Neos\Flow\Mvc\ActionRequest */
        /** @var $widgetContext WidgetContext */
        $widgetContext = $request->getInternalArgument('__widgetContext');
        if ($widgetContext === null) {
            throw new WidgetContextNotFoundException('The widget context could not be found in the request.', 1307450180);
        }
        $this->widgetConfiguration = $widgetContext->getWidgetConfiguration();
        parent::processRequest($request, $response);
    }
}
