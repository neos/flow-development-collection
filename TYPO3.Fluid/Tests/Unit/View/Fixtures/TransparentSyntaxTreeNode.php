<?php
namespace TYPO3\Fluid\View\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * [Enter description here]
 *
 */
class TransparentSyntaxTreeNode extends \TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode
{
    public $variableContainer;

    public function evaluate(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
    }
}
