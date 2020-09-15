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

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * Applies base64_decode to the input
 *
 * @see http://www.php.net/base64_decode
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.base64Decode>{encodedText}</f:format.base64Decode>
 * </code>
 * <output>
 * Text in Base64 encoding will be decoded
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.base64Decode()}
 * </code>
 * <output>
 * Text in Base64 encoding will be decoded
 * </output>
 *
 * @api This is used by the Fluid adaptor internally.
 */
class Base64DecodeViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * Disable the output escaping interceptor so that the result is not htmlspecialchar'd
     *
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
     * Converts all HTML entities to their applicable characters as needed using PHPs html_entity_decode() function.
     *
     * @return string the altered string
     * @api
     */
    public function render()
    {
        $value = $this->arguments['value'];
        if ($value === null) {
            $value = $this->renderChildren();
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            $value = $value->__toString();
        } elseif (!is_string($value)) {
            return $value;
        }

        return base64_decode((string)$value);
    }

    /**
     * This ViewHelper is used whenever something was wrappded in CDATA
     * Therefore we render it to raw PHP code during compilation.
     *
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     * @return string
     * @see \Neos\FluidAdaptor\Core\Parser\TemplateProcessor\NamespaceDetectionTemplateProcessor::protectCDataSectionsFromParser
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        $valueVariableName = $compiler->variableName('value');
        $initializationPhpCode .= sprintf('%1$s = (%2$s[\'value\'] !== NULL ? %2$s[\'value\'] : %3$s());', $valueVariableName, $argumentsName, $closureName) . chr(10);

        return sprintf(
            '!is_string(%1$s) && !(is_object(%1$s) && method_exists(%1$s, \'__toString\')) ? %1$s : base64_decode(%1$s)',
            $valueVariableName
        );
    }
}
