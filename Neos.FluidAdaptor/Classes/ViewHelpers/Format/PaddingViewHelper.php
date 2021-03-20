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
 * Formats a string using PHPs str_pad function.
 *
 * @see http://www.php.net/manual/en/function.str_pad.php
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.padding padLength="10">TYPO3</f:format.padding>
 * </code>
 * <output>
 * TYPO3     (note the trailing whitespace)
 * <output>
 *
 * <code title="Specify padding string">
 * <f:format.padding padLength="10" padString="-=">TYPO3</f:format.padding>
 * </code>
 * <output>
 * TYPO3-=-=-
 * </output>
 *
 * <code title="Specify padding type">
 * <f:format.padding padLength="10" padString="-" padType="both">TYPO3</f:format.padding>
 * </code>
 * <output>
 * --TYPO3---
 * </output>
 *
 * @api
 */
class PaddingViewHelper extends AbstractViewHelper
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
        $this->registerArgument('padLength', 'integer', 'Length of the resulting string. If the value of pad_length is negative or less than the length of the input string, no padding takes place.', true);
        $this->registerArgument('padString', 'string', 'The padding string', false, ' ');
        $this->registerArgument('padType', 'string', 'Append the padding at this site (Possible values: right,left,both. Default: right)', false, 'right');
        $this->registerArgument('value', 'string', 'string to format', false, null);
    }

    /**
     * Pad a string to a certain length with another string
     *
     * @return string The formatted value
     * @api
     */
    public function render()
    {
        return self::renderStatic(['padLength' => $this->arguments['padLength'], 'padString' => $this->arguments['padString'], 'padType' => $this->arguments['padType'], 'value' => $this->arguments['value']], $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies str_pad() on the specified value.
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
        $padTypes = [
            'left' => STR_PAD_LEFT,
            'right' => STR_PAD_RIGHT,
            'both' => STR_PAD_BOTH
        ];
        $padType = $arguments['padType'];
        if (!isset($padTypes[$padType])) {
            $padType = 'right';
        }
        return str_pad($value, $arguments['padLength'], $arguments['padString'], $padTypes[$padType]);
    }
}
