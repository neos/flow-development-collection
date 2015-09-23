<?php
namespace TYPO3\Fluid\Core\Widget;

/*
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Mvc\ResponseInterface;
use TYPO3\Fluid\Core\Widget\Exception\WidgetContextNotFoundException;

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
        /** @var $request \TYPO3\Flow\Mvc\ActionRequest */
        /** @var $widgetContext WidgetContext */
        $widgetContext = $request->getInternalArgument('__widgetContext');
        if ($widgetContext === null) {
            throw new WidgetContextNotFoundException('The widget context could not be found in the request.', 1307450180);
        }
        $this->widgetConfiguration = $widgetContext->getWidgetConfiguration();
        parent::processRequest($request, $response);
    }
}
