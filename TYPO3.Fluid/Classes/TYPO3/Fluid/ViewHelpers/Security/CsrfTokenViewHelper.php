<?php
namespace TYPO3\Fluid\ViewHelpers\Security;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Context;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper that outputs a CSRF token which is required for "unsafe" requests (e.g. POST, PUT, DELETE, ...).
 *
 * Note: You won't need this ViewHelper if you use the Form ViewHelper, because that creates a hidden field with
 * the CSRF token for unsafe requests automatically. This ViewHelper is mainly useful in conjunction with AJAX.
 *
 * = Examples =
 * <code title="Basic usage">
 * <div id="someDiv" data-csrf-token="{f:security.csrfToken()}">
 * ...
 * </div>
 * </code>
 *
 * Now, the CSRF token can be extracted via JavaScript to be appended to requests, for example with jQuery:
 * <code title="fetch CSRF token with jQuery">
 * jQuery (exemplary):
 * $.ajax({
 *   url: '<someEndpoint>',
 *   type: 'POST',
 *   data: {
 *     __csrfToken: $('#someDiv').attr('data-csrf-token')
 *   }
 * });
 * </code>
 */
class CsrfTokenViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @return string
     */
    public function render()
    {
        return $this->securityContext->getCsrfProtectionToken();
    }
}
