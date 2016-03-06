<?php
namespace TYPO3\Fluid\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * "ARGUMENT" -> only has an effect inside of "RENDER". See Render-ViewHelper for documentation.
 *
 * @see \TYPO3\Fluid\ViewHelpers\RenderViewHelper
 * @api
 */
class ArgumentViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Just render everything.
     *
     * The name attribute is mandatory. It is used in the calling RenderViewHelper.
     *
     * @param string $name Name of the argument.
     *
     * @return string the rendered string
     * @api
     */
    public function render($name)
    {
        return $this->renderChildren();
    }
}
