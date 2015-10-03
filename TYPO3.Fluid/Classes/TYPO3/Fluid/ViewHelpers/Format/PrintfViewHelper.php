<?php
namespace TYPO3\Fluid\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * A view helper for formatting values with printf. Either supply an array for
 * the arguments or a single value.
 * See http://www.php.net/manual/en/function.sprintf.php
 *
 * = Examples =
 *
 * <code title="Scientific notation">
 * <f:format.printf arguments="{number: 362525200}">%.3e</f:format.printf>
 * </code>
 * <output>
 * 3.625e+8
 * </output>
 *
 * <code title="Argument swapping">
 * <f:format.printf arguments="{0: 3, 1: 'Kasper'}">%2$s is great, TYPO%1$d too. Yes, TYPO%1$d is great and so is %2$s!</f:format.printf>
 * </code>
 * <output>
 * Kasper is great, TYPO3 too. Yes, TYPO3 is great and so is Kasper!
 * </output>
 *
 * <code title="Single argument">
 * <f:format.printf arguments="{1: 'TYPO3'}">We love %s</f:format.printf>
 * </code>
 * <output>
 * We love TYPO3
 * </output>
 *
 * <code title="Inline notation">
 * {someText -> f:format.printf(arguments: {1: 'TYPO3'})}
 * </code>
 * <output>
 * We love TYPO3
 * </output>
 *
 * @api
 */
class PrintfViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Format the arguments with the given printf format string.
     *
     * @param array $arguments The arguments for vsprintf
     * @param string $value string to format
     * @return string The formatted value
     * @api
     */
    public function render(array $arguments, $value = null)
    {
        return self::renderStatic(array('arguments' => $arguments, 'value' => $value), $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies vsprintf() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        if ($value === null) {
            $value = $renderChildrenClosure();
        }

        return vsprintf($value, $arguments['arguments']);
    }
}
