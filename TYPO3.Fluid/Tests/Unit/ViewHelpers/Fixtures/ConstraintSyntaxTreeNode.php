<?php
namespace TYPO3\Fluid\ViewHelpers\Fixtures;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
