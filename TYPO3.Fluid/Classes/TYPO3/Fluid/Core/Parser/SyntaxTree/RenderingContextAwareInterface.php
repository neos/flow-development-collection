<?php
namespace TYPO3\Fluid\Core\Parser\SyntaxTree;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Interface for objects which are aware of Fluid's rendering context. All objects
 * marked with this interface will get the current rendering context injected
 * by the ObjectAccessorNode on trying to evaluate them.
 *
 */
interface RenderingContextAwareInterface
{
    /**
     * Sets the current rendering context
     *
     * @param RenderingContextInterface $renderingContext
     * @return void
     */
    public function setRenderingContext(RenderingContextInterface $renderingContext);
}
