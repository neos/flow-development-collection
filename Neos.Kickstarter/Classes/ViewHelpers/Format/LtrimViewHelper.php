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
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('charlist', 'string', 'Characters to trim');
    }

    /**
     * @return string The altered string.
     */
    public function render(): string
    {
        $content = $this->renderChildren();
        return ltrim($content, $this->arguments['charlist']);
    }
}
