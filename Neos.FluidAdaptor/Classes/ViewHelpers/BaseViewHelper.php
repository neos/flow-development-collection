<?php
namespace Neos\FluidAdaptor\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper which creates a <base href="..." /> tag. The Base URI
 * is taken from the current request.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:base />
 * </code>
 * <output>
 * <base href="http://yourdomain.tld/" />
 * (depending on your domain)
 * </output>
 *
 * @deprecated since 2.1.0 this ViewHelper is no longer required for regular links and forms
 */
class BaseViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Render the "Base" tag by outputting $httpRequest->getBaseUri()
     *
     * @return string "base"-Tag.
     * @api
     */
    public function render()
    {
        return '<base href="' . htmlspecialchars($this->controllerContext->getRequest()->getHttpRequest()->getBaseUri()) . '" />';
    }
}
