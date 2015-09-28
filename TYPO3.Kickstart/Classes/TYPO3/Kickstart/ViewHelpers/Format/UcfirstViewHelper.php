<?php
namespace TYPO3\Kickstart\ViewHelpers\Format;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Wrapper for PHPs ucfirst function.
 * @see http://www.php.net/manual/en/ucfirst
 *
 * = Examples =
 *
 * <code title="Example">
 * {textWithMixedCase -> k:ucfirst()}
 * </code>
 *
 * Output:
 * TextWithMixedCase
 *
 */
class UcfirstViewHelper extends AbstractViewHelper
{
    /**
     * Uppercase first character
     *
     * @return string The altered string.
     */
    public function render()
    {
        $content = $this->renderChildren();
        return ucfirst($content);
    }
}
