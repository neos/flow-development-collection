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
use TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This view helper implements an ifHasRole/else condition.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifHasRole role="Administrator">
 *   This is being shown in case you have the Administrator role (aka role) defined in the
 *   current package according to the controllerContext
 * </f:security.ifHasRole>
 * </code>
 *
 * <code title="Usage with packageKey attribute">
 * <f:security.ifHasRole role="Administrator" packageKey="Acme.MyPackage">
 *   This is being shown in case you have the Acme.MyPackage:Administrator role (aka role).
 * </f:security.ifHasRole>
 * </code>
 *
 * <code title="Usage with full role identifier in role attribute">
 * <f:security.ifHasRole role="Acme.MyPackage:Administrator">
 *   This is being shown in case you have the Acme.MyPackage:Administrator role (aka role).
 * </f:security.ifHasRole>
 * </code>
 *
 * Everything inside the <f:ifHasRole> tag is being displayed if you have the given role.
 *
 * <code title="IfRole / then / else">
 * <f:security.ifHasRole role="Administrator">
 *   <f:then>
 *     This is being shown in case you have the role.
 *   </f:then>
 *   <f:else>
 *     This is being displayed in case you do not have the role.
 *   </f:else>
 * </f:security.ifHasRole>
 * </code>
 *
 * Everything inside the "then" tag is displayed if you have the role.
 * Otherwise, everything inside the "else"-tag is displayed.
 *
 * @api
 */
class IfHasRoleViewHelper extends AbstractConditionViewHelper
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * renders <f:then> child if the role could be found in the security context,
     * otherwise renders <f:else> child.
     *
     * @param string $role The role
     * @param string $packageKey PackageKey of the package defining the role
     * @return string the rendered string
     * @api
     */
    public function render($role, $packageKey = null)
    {
        if ($role !== 'Everybody' && $role !== 'Anonymous' && $role !== 'AuthenticatedUser' && strpos($role, '.') === false && strpos($role, ':') === false) {
            if ($packageKey === null) {
                $request = $this->controllerContext->getRequest();
                $role = $request->getControllerPackageKey() . ':' . $role;
            } else {
                $role = $packageKey . ':' . $role;
            }
        }

        if ($this->securityContext->hasRole($role)) {
            return $this->renderThenChild();
        } else {
            return $this->renderElseChild();
        }
    }
}
