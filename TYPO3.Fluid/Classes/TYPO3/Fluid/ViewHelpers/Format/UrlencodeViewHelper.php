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
use TYPO3\Fluid\Core\ViewHelper;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception;
use TYPO3\Fluid\Core\ViewHelper\Facets\CompilableInterface;

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
class UrlencodeViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs urlencode() function.
     *
     * @param string $value string to format
     * @return mixed
     * @see http://www.php.net/manual/function.urlencode.php
     * @api
     * @throws ViewHelper\Exception
     */
    public function render($value = null)
    {
        return self::renderStatic(array('value' => $value), $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Applies rawurlencode() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     * @throws \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        if ($value === null) {
            $value = $renderChildrenClosure();
        }
        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new ViewHelper\Exception(sprintf('This ViewHelper works with values that are of type string or objects that implement a __toString method. You provided "%s"', is_object($value) ? get_class($value) : gettype($value)), 1359389241);
        }

        return rawurlencode($value);
    }
}
