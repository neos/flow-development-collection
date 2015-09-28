<?php
namespace TYPO3\Kickstart\ViewHelpers\Inflect;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
