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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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
     * Outputs content with its JSON representation. To prevent issues in HTML context, occurrences
     * of greater-than or less-than characters are converted to their hexadecimal representations.
     *
     * If $forceObject is TRUE a JSON object is outputted even if the value is a non-associative array
     * Example: array('foo', 'bar') as input will not be ["foo","bar"] but {"0":"foo","1":"bar"}
     *
     * @param mixed $value The incoming data to convert, or NULL if VH children should be used
     * @param boolean $forceObject Outputs an JSON object rather than an array
     * @return string the JSON-encoded string.
     * @see http://www.php.net/manual/en/function.json-encode.php
     * @api
     */
    public function render($value = null, $forceObject = false)
    {
        return self::renderStatic(array('value' => $value, 'forceObject' => $forceObject), $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies json_encode() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        if ($value === null) {
            $value = $renderChildrenClosure();
        }
        $options = JSON_HEX_TAG;
        if ($arguments['forceObject'] !== false) {
            $options = $options | JSON_FORCE_OBJECT;
        }

        return json_encode($value, $options);
    }
}
