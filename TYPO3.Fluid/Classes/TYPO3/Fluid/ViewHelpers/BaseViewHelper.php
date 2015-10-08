<?php
namespace TYPO3\Fluid\ViewHelpers;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

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
