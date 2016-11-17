<?php
namespace Neos\FluidAdaptor\ViewHelpers\Format;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Outputs an argument/value without any escaping. Is normally used to output
 * an ObjectAccessor which should not be escaped, but output as-is.
 *
 * PAY SPECIAL ATTENTION TO SECURITY HERE (especially Cross Site Scripting),
 * as the output is NOT SANITIZED!
 *
 * = Examples =
 *
 * <code title="Child nodes">
 * <f:format.raw>{string}</f:format.raw>
 * </code>
 * <output>
 * (Content of {string} without any conversion/escaping)
 * </output>
 *
 * <code title="Value attribute">
 * <f:format.raw value="{string}" />
 * </code>
 * <output>
 * (Content of {string} without any conversion/escaping)
 * </output>
 *
 * <code title="Inline notation">
 * {string -> f:format.raw()}
 * </code>
 * <output>
 * (Content of {string} without any conversion/escaping)
 * </output>
 *
 * @api
 */
class RawViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\Format\RawViewHelper
{
    /**
     * @var boolean
     */
//    protected $escapeChildren = false;

    /**
     * @var boolean
     */
//    protected $escapeOutput = false;

    /**
     * @param mixed $value The value to output
     * @return string
     */
//    public function render($value = null)
//    {
//        return static::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
//    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
//    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
//    {
//        if (isset($arguments['value'])) {
//            return $arguments['value'];
//        }
//
//        return $renderChildrenClosure();
//    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     * @return mixed
     */
//    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
//    {
//        return sprintf("isset(%s['value']) ? %s['value'] : %s()", $argumentsName, $argumentsName, $closureName);
//    }
}
