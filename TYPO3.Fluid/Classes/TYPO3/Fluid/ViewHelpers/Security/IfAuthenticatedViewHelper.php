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

use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
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
     * renders <f:then> child if an account is authenticated, otherwise renders <f:else> child.
     *
     * @return string the rendered then/else child nodes depending on the access
     * @api
     */
    public function render()
    {
        return $this->renderInternal();
    }

    /**
     * @param array $arguments
     * @param RenderingContextInterface $renderingContext
     * @return boolean
     */
    protected static function evaluateCondition($arguments = null, RenderingContextInterface $renderingContext)
    {
        /** @var ObjectManagerInterface $objectManager */
        $objectManager = $renderingContext->getObjectManager();
        $securityContext = $objectManager->get(Context::class);

        /** @var $token TokenInterface */
        foreach ($securityContext->getAuthenticationTokens() as $token) {
            if ($token->isAuthenticated()) {
                return true;
            }
        }
        return false;
    }
}
