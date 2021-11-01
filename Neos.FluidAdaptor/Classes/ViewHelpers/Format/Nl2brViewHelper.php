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
 * newline characters in a string get prefixed with <br /> to create linebreak
 * </output>
 *
 * <code title="Inline notation">
 * {text_with_linebreaks -> f:format.nl2br()}
 * </code>
 * <output>
 * newline characters in a string get prefixed with <br /> to create linebreak
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
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'string', 'string to format', false, null);
    }

    /**
     * Replaces newline characters by HTML line breaks.
     *
     * @return string the altered string.
     * @api
     */
    public function render()
    {
        $value = $this->arguments['value'];

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
