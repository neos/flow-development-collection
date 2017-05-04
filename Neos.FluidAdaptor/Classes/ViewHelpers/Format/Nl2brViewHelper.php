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
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Wrapper for PHPs nl2br function.
 *
 * @see http://www.php.net/manual/en/function.nl2br.php
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:format.nl2br>{text_with_linebreaks}</f:format.nl2br>
 * </code>
 * <output>
 * text with line breaks replaced by <br />
 * </output>
 *
 * <code title="Inline notation">
 * {text_with_linebreaks -> f:format.nl2br()}
 * </code>
 * <output>
 * text with line breaks replaced by <br />
 * </output>
 *
 * @api
 */
class Nl2brViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Replaces newline characters by HTML line breaks.
     *
     * @param string $value string to format
     * @return string the altered string.
     * @api
     */
    public function render($value = null)
    {
        if ($value === null) {
            $value = $this->renderChildren();
        }

        return nl2br($value);
    }

    /**
     * Compile to direct nl2br use in template code.
     *
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     * @return string
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        $valueVariableName = $compiler->variableName('value');
        $initializationPhpCode .= sprintf('%1$s = (%2$s[\'value\'] !== null ? %2$s[\'value\'] : %3$s());', $valueVariableName, $argumentsName, $closureName) . chr(10);

        return sprintf('nl2br(%1$s)', $valueVariableName);
    }
}
