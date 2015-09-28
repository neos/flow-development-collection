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
 * Wrapper for PHPs ltrim function.
 * @see http://www.php.net/manual/en/ltrim
 *
 * = Examples =
 *
 * <code title="Example">
 * {someVariable -> k:format.ltrim()}
 * </code>
 *
 * Output:
 * content of {someVariable} with ltrim applied
 *
 */
class LtrimViewHelper extends AbstractViewHelper
{
    /**
     * @param string $charlist
     * @return string The altered string.
     */
    public function render($charlist = null)
    {
        $content = $this->renderChildren();
        return ltrim($content, $charlist);
    }
}
