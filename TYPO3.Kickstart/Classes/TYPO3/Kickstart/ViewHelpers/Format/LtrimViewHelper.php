<?php
namespace TYPO3\Kickstart\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Kickstart package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
