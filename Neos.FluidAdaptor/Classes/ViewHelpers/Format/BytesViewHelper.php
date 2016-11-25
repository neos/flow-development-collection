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

use Neos\Utility\Files;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Formats an integer with a byte count into human-readable form.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * {fileSize -> f:format.bytes()}
 * </code>
 * <output>
 * 123 KB
 * // depending on the value of {fileSize}
 * </output>
 *
 * <code title="Defaults">
 * {fileSize -> f:format.bytes(decimals: 2, decimalSeparator: ',', thousandsSeparator: ',')}
 * </code>
 * <output>
 * 1,023.00 B
 * // depending on the value of {fileSize}
 * </output>
 *
 * @api
 */
class BytesViewHelper extends AbstractViewHelper
{
    /**
     * Render the supplied byte count as a human readable string.
     *
     * @param integer $value The incoming data to convert, or NULL if VH children should be used
     * @param integer $decimals The number of digits after the decimal point
     * @param string $decimalSeparator The decimal point character
     * @param string $thousandsSeparator The character for grouping the thousand digits
     * @return string Formatted byte count
     * @api
     */
    public function render($value = null, $decimals = 0, $decimalSeparator = '.', $thousandsSeparator = ',')
    {
        return self::renderStatic(array('value' => $value, 'decimals' => $decimals, 'decimalSeparator' => $decimalSeparator, 'thousandsSeparator' => $thousandsSeparator), $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies htmlspecialchars() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        if ($value === null) {
            $value = $renderChildrenClosure();
        }
        if (!is_integer($value) && !is_float($value)) {
            if (is_numeric($value)) {
                $value = (float)$value;
            } else {
                $value = 0;
            }
        }
        return Files::bytesToSizeString($value, $arguments['decimals'], $arguments['decimalSeparator'], $arguments['thousandsSeparator']);
    }
}
