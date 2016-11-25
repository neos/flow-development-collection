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
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractConditionViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * This view helper implements an ifAccess/else condition.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifAccess privilegeTarget="somePrivilegeTargetIdentifier">
 *   This is being shown in case you have access to the given privilege
 * </f:security.ifAccess>
 * </code>
 *
 * Everything inside the <f:ifAccess> tag is being displayed if you have access to the given privilege.
 *
 * <code title="IfAccess / then / else">
 * <f:security.ifAccess privilegeTarget="somePrivilegeTargetIdentifier">
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
 * <code title="Inline syntax with privilege parameters">
 * {f:security.ifAccess(privilegeTarget: 'someTarget', parameters: '{param1: \'value1\'}', then: 'has access', else: 'has no access')}
 * </code>
 *
 * @api
 */
class IfAccessViewHelper extends AbstractConditionViewHelper
{

    /**
     * Initializes the "then" and "else" arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('then', 'mixed', 'Value to be returned if the condition if met.', false);
        $this->registerArgument('else', 'mixed', 'Value to be returned if the condition if not met.', false);
        $this->registerArgument('privilegeTarget', 'string', 'Condition expression conforming to Fluid boolean rules', true);
        $this->registerArgument('parameters', 'array', 'Condition expression conforming to Fluid boolean rules', false, []);
    }

    /**
     * renders <f:then> child if access to the given resource is allowed, otherwise renders <f:else> child.
     *
     * @return string the rendered then/else child nodes depending on the access
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
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return static::renderResult(static::evaluateCondition($arguments, $renderingContext), $arguments, $renderingContext);
    }

    /**
     * @param null $arguments
     * @param RenderingContextInterface $renderingContext
     * @return boolean
     */
    protected static function evaluateCondition($arguments = null, RenderingContextInterface $renderingContext)
    {
        $privilegeManager = static::getPrivilegeManager($renderingContext);
        return $privilegeManager->isPrivilegeTargetGranted($arguments['privilegeTarget'], $arguments['parameters']);
    }

    /**
     * @param RenderingContext $renderingContext
     * @return PrivilegeManagerInterface
     */
    protected static function getPrivilegeManager(RenderingContext $renderingContext)
    {
        $objectManager = $renderingContext->getObjectManager();
        return $objectManager->get(PrivilegeManagerInterface::class);
    }
}
