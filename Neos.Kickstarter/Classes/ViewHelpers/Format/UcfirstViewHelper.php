<?php
namespace Neos\Kickstarter\ViewHelpers\Format;

/*
 * This file is part of the Neos.Kickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

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
