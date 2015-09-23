<?php
namespace TYPO3\Fluid\ViewHelpers\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Constraint syntax tree node fixture
 */
class ConstraintSyntaxTreeNode extends \TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
{
    public $callProtocol = array();

    public function __construct(\TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer)
    {
        $this->variableContainer = $variableContainer;
    }

    public function evaluateChildNodes(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        $identifiers = $this->variableContainer->getAllIdentifiers();
        $callElement = array();
        foreach ($identifiers as $identifier) {
            $callElement[$identifier] = $this->variableContainer->get($identifier);
        }
        $this->callProtocol[] = $callElement;
    }

    public function evaluate(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
    }
}
