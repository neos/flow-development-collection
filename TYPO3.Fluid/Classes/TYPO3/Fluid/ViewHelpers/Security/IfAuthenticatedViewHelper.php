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

use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This view helper implements an ifAuthenticated/else condition.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifAuthenticated>
 *   This is being shown whenever a user is logged in
 * </f:security.ifAuthenticated>
 * </code>
 *
 * Everything inside the <f:ifAuthenticated> tag is being displayed if you are authenticated with any account.
 *
 * <code title="IfAuthenticated / then / else">
 * <f:security.ifAuthenticated>
 *   <f:then>
 *     This is being shown in case you have access.
 *   </f:then>
 *   <f:else>
 *     This is being displayed in case you do not have access.
 *   </f:else>
 * </f:security.ifAuthenticated>
 * </code>
 *
 * Everything inside the "then" tag is displayed if you have access.
 * Otherwise, everything inside the "else"-tag is displayed.
 *
 *
 *
 * @api
 */
class IfAuthenticatedViewHelper extends AbstractConditionViewHelper
{
    /**
     * @var Context
     */
    protected $securityContext;

    /**
     * Injects the Security Context
     *
     * @param Context $securityContext
     * @return void
     */
    public function injectSecurityContext(Context $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Renders <f:then> child if any account is currently authenticated, otherwise renders <f:else> child.
     *
     * @return string the rendered string
     * @api
     */
    public function render()
    {
        $activeTokens = $this->securityContext->getAuthenticationTokens();
        /** @var $token TokenInterface */
        foreach ($activeTokens as $token) {
            if ($token->isAuthenticated()) {
                return $this->renderThenChild();
            }
        }
        return $this->renderElseChild();
    }
}
