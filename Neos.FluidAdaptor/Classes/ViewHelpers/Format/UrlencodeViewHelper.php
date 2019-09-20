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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use Neos\FluidAdaptor\Core\ViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Encodes the given string according to http://www.faqs.org/rfcs/rfc3986.html (applying PHPs rawurlencode() function)
 *
 * @see http://www.php.net/manual/function.urlencode.php
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.urlencode>foo @+%/</f:format.urlencode>
 * </code>
 * <output>
 * foo%20%40%2B%25%2F (rawurlencode() applied)
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.urlencode()}
 * </code>
 * <output>
 * Url encoded text (rawurlencode() applied)
 * </output>
 *
 * @api
 */
class UrlencodeViewHelper extends AbstractViewHelper
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
        $this->registerArgument('value', 'string', 'string to format', false, null);
    }

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs urlencode() function.
     *
     * @return mixed
     * @see http://www.php.net/manual/function.urlencode.php
     * @api
     * @throws ViewHelper\Exception
     */
    public function render()
    {
        return self::renderStatic(['value' => $this->arguments['value']], $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies rawurlencode() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
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

        return rawurlencode($value);
    }
}
