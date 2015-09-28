<?php
namespace TYPO3\Fluid\Core\Rendering;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;
use TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Contract for the rendering context
 */
interface RenderingContextInterface
{
    /**
     * Injects the template variable container containing all variables available through ObjectAccessors
     * in the template
     *
     * @param TemplateVariableContainer $templateVariableContainer The template variable container to set
     */
    public function injectTemplateVariableContainer(TemplateVariableContainer $templateVariableContainer);

    /**
     * Get the template variable container
     *
     * @return TemplateVariableContainer The Template Variable Container
     */
    public function getTemplateVariableContainer();

    /**
     * Set the controller context which will be passed to the ViewHelper
     *
     * @param ControllerContext $controllerContext The controller context to set
     */
    public function setControllerContext(ControllerContext $controllerContext);

    /**
     * Get the controller context which will be passed to the ViewHelper
     *
     * @return ControllerContext The controller context to set
     */
    public function getControllerContext();

    /**
     * Get the ViewHelperVariableContainer
     *
     * @return ViewHelperVariableContainer
     */
    public function getViewHelperVariableContainer();
}
