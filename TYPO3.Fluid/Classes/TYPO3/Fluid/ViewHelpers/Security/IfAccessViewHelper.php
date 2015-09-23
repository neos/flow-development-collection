<?php
namespace TYPO3\Fluid\ViewHelpers\Security;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This view helper implements an ifAccess/else condition.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifAccess resource="someResource">
 *   This is being shown in case you have access to the given resource
 * </f:security.ifAccess>
 * </code>
 *
 * Everything inside the <f:ifAccess> tag is being displayed if you have access to the given resource.
 *
 * <code title="IfAccess / then / else">
 * <f:security.ifAccess resource="someResource">
 *   <f:then>
 *     This is being shown in case you have access.
 *   </f:then>
 *   <f:else>
 *     This is being displayed in case you do not have access.
 *   </f:else>
 * </f:security.ifAccess>
 * </code>
 *
 * Everything inside the "then" tag is displayed if you have access.
 * Otherwise, everything inside the "else"-tag is displayed.
 *
 *
 *
 * @api
 */
class IfAccessViewHelper extends AbstractConditionViewHelper
{
    /**
     * @var AccessDecisionManagerInterface
     */
    protected $accessDecisionManager;

    /**
     * Injects the access decision manager
     *
     * @param AccessDecisionManagerInterface $accessDecisionManager The access decision manager
     * @return void
     */
    public function injectAccessDecisionManager(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }

    /**
     * renders <f:then> child if access to the given resource is allowed, otherwise renders <f:else> child.
     *
     * @param string $resource Policy resource
     * @return string the rendered string
     * @api
     */
    public function render($resource)
    {
        if ($this->accessDecisionManager->hasAccessToResource($resource)) {
            return $this->renderThenChild();
        } else {
            return $this->renderElseChild();
        }
    }
}
