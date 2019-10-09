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
 * Applies htmlentities() escaping to a value
 * @see http://www.php.net/manual/function.htmlentities.php
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.htmlentities>{text}</f:format.htmlentities>
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlentities applied).
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.htmlentities(encoding: 'ISO-8859-1')}
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlentities applied).
 * </output>
 *
 * @api
 */
class HtmlentitiesViewHelper extends AbstractViewHelper
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
        $this->registerArgument('keepQuotes', 'boolean', 'if true, single and double quotes won\'t be replaced (sets ENT_NOQUOTES flag)', false, false);
        $this->registerArgument('encoding', 'string', 'the encoding format', false, 'UTF-8');
        $this->registerArgument('doubleEncode', 'string', 'If false existing html entities won\'t be encoded, the default is to convert everything.', false, true);
    }

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs htmlentities() function.
     *
     * @return string the altered string
     * @see http://www.php.net/manual/function.htmlentities.php
     * @api
     */
    public function render()
    {
        return self::renderStatic(['value' => $this->arguments['value'], 'keepQuotes' => $this->arguments['keepQuotes'], 'encoding' => $this->arguments['encoding'], 'doubleEncode' => $this->arguments['doubleEncode']], $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies htmlentities() on the specified value.
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
        if (is_object($value) && method_exists($value, '__toString')) {
            $value = $value->__toString();
        } elseif (!is_string($value)) {
            return $value;
        }
        $flags = $arguments['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT;
        return htmlentities($value, $flags, $arguments['encoding'], $arguments['doubleEncode']);
    }
}
