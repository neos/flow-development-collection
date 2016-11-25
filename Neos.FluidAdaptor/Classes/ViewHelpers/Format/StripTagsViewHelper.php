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
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Removes tags from the given string (applying PHPs strip_tags() function)
 *
 * @see http://www.php.net/manual/function.strip-tags.php
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.stripTags>Some Text with <b>Tags</b> and an &Uuml;mlaut.</f:format.stripTags>
 * </code>
 * <output>
 * Some Text with Tags and an &Uuml;mlaut. (strip_tags() applied. Note: encoded entities are not decoded)
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.stripTags()}
 * </code>
 * <output>
 * Text without tags (strip_tags() applied)
 * </output>
 *
 * @api
 */
class StripTagsViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs strip_tags() function.
     *
     * @param string $value string to format
     * @return mixed
     * @see http://www.php.net/manual/function.strip-tags.php
     * @api
     */
    public function render($value = null)
    {
        return self::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies strip_tags() on the specified value.
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
        if (is_string($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return strip_tags($value);
        }

        return $value;
    }
}
