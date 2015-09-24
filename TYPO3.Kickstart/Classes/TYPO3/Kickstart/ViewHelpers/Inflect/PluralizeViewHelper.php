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
 * Pluralize a word
 *
 * = Examples =
 *
 * <code title="Example">
 * {variable -> k:inflect.pluralize()}
 * </code>
 *
 * Output:
 * content of {variable} in its plural form (foo => foos)
 *
 */
class PluralizeViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var \TYPO3\Kickstart\Utility\Inflector
     * @Flow\Inject
     */
    protected $inflector;

    /**
     * Pluralize a word
     *
     * @return string The pluralized string
     */
    public function render()
    {
        $content = $this->renderChildren();
        return $this->inflector->pluralize($content);
    }
}
