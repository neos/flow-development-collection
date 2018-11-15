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
 * Wrapper for PHPs json_encode function.
 *
 * = Examples =
 *
 * <code title="encoding a view variable">
 * {someArray -> f:format.json()}
 * </code>
 * <output>
 * ["array","values"]
 * // depending on the value of {someArray}
 * </output>
 *
 * <code title="associative array">
 * {f:format.json(value: {foo: 'bar', bar: 'baz'})}
 * </code>
 * <output>
 * {"foo":"bar","bar":"baz"}
 * </output>
 *
 * <code title="non-associative array with forced object">
 * {f:format.json(value: {0: 'bar', 1: 'baz'}, forceObject: true)}
 * </code>
 * <output>
 * {"0":"bar","1":"baz"}
 * </output>
 *
 * @api
 */
class JsonViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'The incoming data to convert, or NULL if VH children should be used', false, null);
        $this->registerArgument('forceObject', 'boolean', 'Outputs an JSON object rather than an array', false, false);
    }

    /**
     * Outputs content with its JSON representation. To prevent issues in HTML context, occurrences
     * of greater-than or less-than characters are converted to their hexadecimal representations.
     *
     * If $forceObject is true a JSON object is outputted even if the value is a non-associative array
     * Example: array('foo', 'bar') as input will not be ["foo","bar"] but {"0":"foo","1":"bar"}
     *
     * @return string the JSON-encoded string.
     * @see http://www.php.net/manual/en/function.json-encode.php
     * @api
     */
    public function render()
    {
        $value = $this->arguments['value'];

        if ($value === null) {
            $value = $this->renderChildren();
        }
        $options = JSON_HEX_TAG;
        if ($this->arguments['forceObject'] !== false) {
            $options = $options | JSON_FORCE_OBJECT;
        }

        return json_encode($value, $options);
    }

    /**
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
        $optionsVariableName = $compiler->variableName('options');
        $initializationPhpCode .= sprintf('%1$s = (%2$s[\'value\'] !== null ? %2$s[\'value\'] : %3$s());', $valueVariableName, $argumentsName, $closureName) . chr(10);
        $initializationPhpCode .= sprintf('%1$s = %2$s[\'forceObject\'] !== false ? JSON_HEX_TAG |JSON_FORCE_OBJECT : JSON_HEX_TAG;', $optionsVariableName, $argumentsName) . chr(10);

        return sprintf(
            'json_encode(%1$s, %2$s)',
            $valueVariableName,
            $optionsVariableName
        );
    }
}
