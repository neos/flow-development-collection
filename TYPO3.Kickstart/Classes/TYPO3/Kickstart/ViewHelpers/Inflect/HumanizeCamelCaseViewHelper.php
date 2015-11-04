<?php
namespace TYPO3\Kickstart\ViewHelpers\Inflect;

/*
 * This file is part of the TYPO3.Kickstart package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Humanize a camel cased value
 *
 * = Examples =
 *
 * <code title="Example">
 * {CamelCasedModelName -> k:inflect.humanizeCamelCase()}
 * </code>
 *
 * Output:
 * Camel cased model name
 *
 */
class HumanizeCamelCaseViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var \TYPO3\Kickstart\Utility\Inflector
     * @Flow\Inject
     */
    protected $inflector;

    /**
     * Humanize a model name
     *
     * @param boolean $lowercase Wether the result should be lowercased
     * @return string The humanized string
     */
    public function render($lowercase = false)
    {
        $content = $this->renderChildren();
        return $this->inflector->humanizeCamelCase($content, $lowercase);
    }
}
