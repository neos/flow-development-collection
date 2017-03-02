<?php
namespace Neos\FluidAdaptor\ViewHelpers\Security;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractConditionViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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
 * <code title="Usage with role object in role attribute">
 * <f:security.ifHasRole role="{someRoleObject}">
 *   This is being shown in case you have the specified role
 * </f:security.ifHasRole>
 * </code>
 *
 * <code title="Usage with specific account instead of currently logged in account">
 * <f:security.ifHasRole role="Administrator" account="{otherAccount}">
 *   This is being shown in case "otherAccount" has the Acme.MyPackage:Administrator role (aka role).
 * </f:security.ifHasRole>
 * </code>
 *
 *
 * @api
 */
class IfHasRoleViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes the "then" and "else" arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('role', 'mixed', 'The role or role identifier.', true);
        $this->registerArgument('packageKey', 'string', 'PackageKey of the package defining the role.', false, null);
        $this->registerArgument('account', Account::class, 'If specified, this subject of this check is the given Account instead of the currently authenticated account', false, null);
        $this->registerArgument('then', 'mixed', 'Value to be returned if the condition if met.', false);
        $this->registerArgument('else', 'mixed', 'Value to be returned if the condition if not met.', false);
    }

    /**
     * renders <f:then> child if the role could be found in the security context,
     * otherwise renders <f:else> child.
     *
     * @return string the rendered string
     * @api
     */
    public function render()
    {
        if (static::evaluateCondition($this->arguments, $this->renderingContext)) {
            return $this->renderThenChild();
        }

        return $this->renderElseChild();
    }

    /**
     * @param null $arguments
     * @param RenderingContextInterface $renderingContext
     * @return boolean
     */
    protected static function evaluateCondition($arguments = null, RenderingContextInterface $renderingContext)
    {
        $objectManager = $renderingContext->getObjectManager();
        /** @var PolicyService $policyService */
        $policyService = $objectManager->get(PolicyService::class);
        /** @var Context $securityContext */
        $securityContext = $objectManager->get(Context::class);

        $role = $arguments['role'];
        $account = $arguments['account'];
        $packageKey = isset($arguments['packageKey']) ? $arguments['packageKey'] : $renderingContext->getControllerContext()->getRequest()->getControllerPackageKey();

        if (is_string($role)) {
            $roleIdentifier = $role;

            if (in_array($roleIdentifier, ['Everybody', 'Anonymous', 'AuthenticatedUser'])) {
                $roleIdentifier = 'Neos.Flow:' . $roleIdentifier;
            }

            if (strpos($roleIdentifier, '.') === false && strpos($roleIdentifier, ':') === false) {
                $roleIdentifier = $packageKey . ':' . $roleIdentifier;
            }

            $role = $policyService->getRole($roleIdentifier);
        }

        $hasRole = $securityContext->hasRole($role->getIdentifier());
        if ($account instanceof Account) {
            $hasRole = $account->hasRole($role);
        }

        return $hasRole;
    }
}
