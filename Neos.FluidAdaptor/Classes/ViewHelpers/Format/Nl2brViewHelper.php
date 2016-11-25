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
        return self::renderStatic(array('value' => $value), $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies nl2br() on the specified value.
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

        return nl2br($value);
    }
}
