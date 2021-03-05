<?php
namespace Neos\FluidAdaptor\ViewHelpers\Widget;

/*
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;

/**
 * widget.uri ViewHelper
 * This ViewHelper can be used inside widget templates in order to render URIs pointing to widget actions
 *
 * = Examples =
 *
 * <code>
 * {f:widget.uri(action: 'widgetAction')}
 * </code>
 * <output>
 *  --widget[@action]=widgetAction
 *  (depending on routing setup and current widget)
 * </output>
 *
 * @api
 */
class UriViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('action', 'string', 'Target action', true);
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html"', false, '');
        $this->registerArgument('ajax', 'boolean', 'true if the URI should be to an AJAX widget, false otherwise', false, false);
        $this->registerArgument('includeWidgetContext', 'boolean', 'true if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)', false, false);
    }

    /**
     * Render the Uri.
     *
     * @return string The rendered link
     * @throws ViewHelper\Exception if $action argument is not specified and $ajax is false
     * @throws WidgetContextNotFoundException
     * @api
     */
    public function render(): string
    {
        if ($this->arguments['ajax'] === true) {
            return $this->getAjaxUri();
        }

        if (!$this->hasArgument('action')) {
            throw new ViewHelper\Exception('You have to specify the target action when creating a widget URI with the widget.uri ViewHelper', 1357648232);
        }
        return $this->getWidgetUri();
    }

    /**
     * Get the URI for an AJAX Request.
     *
     * @return string the AJAX URI
     * @throws WidgetContextNotFoundException
     */
    protected function getAjaxUri(): string
    {
        $action = $this->arguments['action'];
        $arguments = $this->arguments['arguments'];

        if ($action === null) {
            $action = $this->controllerContext->getRequest()->getControllerActionName();
        }
        $arguments['@action'] = $action;
        if ($this->arguments['format'] !== '') {
            $arguments['@format'] = $this->arguments['format'];
        }
        /** @var $widgetContext WidgetContext */
        $widgetContext = $this->controllerContext->getRequest()->getInternalArgument('__widgetContext');
        if (!$widgetContext instanceof WidgetContext) {
            throw new WidgetContextNotFoundException('Widget context not found in <f:widget.uri>', 1307450639);
        }
        if ($this->arguments['includeWidgetContext'] === true) {
            $serializedWidgetContext = base64_encode(serialize($widgetContext));
            $arguments['__widgetContext'] = $this->hashService->appendHmac($serializedWidgetContext);
        } else {
            $arguments['__widgetId'] = $widgetContext->getAjaxWidgetIdentifier();
        }
        return '?' . http_build_query($arguments, null, '&');
    }

    /**
     * Get the URI for a non-AJAX Request.
     *
     * @return string the Widget URI
     * @throws ViewHelper\Exception
     * @todo argumentsToBeExcludedFromQueryString does not work yet, needs to be fixed.
     */
    protected function getWidgetUri(): string
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();

        $argumentsToBeExcludedFromQueryString = [
            '@package',
            '@subpackage',
            '@controller'
        ];

        $uriBuilder
            ->reset()
            ->setSection($this->arguments['section'])
            ->setCreateAbsoluteUri(true)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
            ->setFormat($this->arguments['format']);
        try {
            $uri = $uriBuilder->uriFor($this->arguments['action'], $this->arguments['arguments'], '', '', '');
        } catch (\Exception $exception) {
            throw new ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
        return $uri;
    }
}
