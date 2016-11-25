<?php
namespace Neos\FluidAdaptor\ViewHelpers;

/*
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\Widget\Exception\RenderingContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Render the inner parts of a Widget.
 * This ViewHelper can only be used in a template which belongs to a Widget Controller.
 *
 * It renders everything inside the Widget ViewHelper, and you can pass additional
 * arguments.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <!-- in the widget template -->
 * Header
 * <f:renderChildren arguments="{foo: 'bar'}" />
 * Footer
 *
 * <-- in the outer template, using the widget -->
 *
 * <x:widget.someWidget>
 *   Foo: {foo}
 * </x:widget.someWidget>
 * </code>
 * <output>
 * Header
 * Foo: bar
 * Footer
 * </output>
 *
 * @api
 */
class RenderChildrenViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @param array $arguments
     * @return string
     */
    public function render(array $arguments = array())
    {
        $renderingContext = $this->getWidgetRenderingContext();
        $widgetChildNodes = $this->getWidgetChildNodes();

        $this->addArgumentsToTemplateVariableContainer($arguments);
        $output = $widgetChildNodes->evaluate($renderingContext);
        $this->removeArgumentsFromTemplateVariableContainer($arguments);

        return $output;
    }

    /**
     * Get the widget rendering context, or throw an exception if it cannot be found.
     *
     * @return RenderingContextInterface
     * @throws RenderingContextNotFoundException
     */
    protected function getWidgetRenderingContext()
    {
        $renderingContext = $this->getWidgetContext()->getViewHelperChildNodeRenderingContext();
        if (!($renderingContext instanceof RenderingContextInterface)) {
            throw new RenderingContextNotFoundException('Rendering Context not found inside Widget. <f:renderChildren> has been used in an AJAX Request, but is only usable in non-ajax mode.', 1284986604);
        }
        return $renderingContext;
    }

    /**
     * @return RootNode
     */
    protected function getWidgetChildNodes()
    {
        return $this->getWidgetContext()->getViewHelperChildNodes();
    }

    /**
     * @return WidgetContext
     * @throws WidgetContextNotFoundException
     */
    protected function getWidgetContext()
    {
        $request = $this->controllerContext->getRequest();
        /** @var $widgetContext WidgetContext */
        $widgetContext = $request->getInternalArgument('__widgetContext');
        if ($widgetContext === null) {
            throw new WidgetContextNotFoundException('The Request does not contain a widget context! <f:renderChildren> must be called inside a Widget Template.', 1284986120);
        }

        return $widgetContext;
    }

    /**
     * Add the given arguments to the TemplateVariableContainer of the widget.
     *
     * @param array $arguments
     * @return void
     */
    protected function addArgumentsToTemplateVariableContainer(array $arguments)
    {
        $templateVariableContainer = $this->getWidgetRenderingContext()->getVariableProvider();
        foreach ($arguments as $identifier => $value) {
            $templateVariableContainer->add($identifier, $value);
        }
    }

    /**
     * Remove the given arguments from the TemplateVariableContainer of the widget.
     *
     * @param array $arguments
     * @return void
     */
    protected function removeArgumentsFromTemplateVariableContainer(array $arguments)
    {
        $templateVariableContainer = $this->getWidgetRenderingContext()->getVariableProvider();
        foreach ($arguments as $identifier => $value) {
            $templateVariableContainer->remove($identifier);
        }
    }
}
