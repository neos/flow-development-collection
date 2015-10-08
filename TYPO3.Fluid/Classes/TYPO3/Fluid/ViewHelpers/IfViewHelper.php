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

use TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This view helper implements an if/else condition.
 * Check \TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::convertArgumentValue() to see how boolean arguments are evaluated
 *
 * **Conditions:**
 *
 * As a condition is a boolean value, you can just use a boolean argument.
 * Alternatively, you can write a boolean expression there.
 * Boolean expressions have the following form:
 * XX Comparator YY
 * Comparator is one of: ==, !=, <, <=, >, >= and %
 * The % operator converts the result of the % operation to boolean.
 *
 * XX and YY can be one of:
 * - number
 * - string
 * - Object Accessor
 * - Array
 * - a ViewHelper
 * ::
 *
 *   <f:if condition="{rank} > 100">
 *     Will be shown if rank is > 100
 *   </f:if>
 *   <f:if condition="{rank} % 2">
 *     Will be shown if rank % 2 != 0.
 *   </f:if>
 *   <f:if condition="{rank} == {k:bar()}">
 *     Checks if rank is equal to the result of the ViewHelper "k:bar"
 *   </f:if>
 *   <f:if condition="{foo.bar} == 'stringToCompare'">
 *     Will result true if {foo.bar}'s represented value equals 'stringToCompare'.
 *   </f:if>
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:if condition="somecondition">
 *   This is being shown in case the condition matches
 * </f:if>
 * </code>
 * <output>
 * Everything inside the <f:if> tag is being displayed if the condition evaluates to TRUE.
 * </output>
 *
 * <code title="If / then / else">
 * <f:if condition="somecondition">
 *   <f:then>
 *     This is being shown in case the condition matches.
 *   </f:then>
 *   <f:else>
 *     This is being displayed in case the condition evaluates to FALSE.
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Everything inside the "then" tag is displayed if the condition evaluates to TRUE.
 * Otherwise, everything inside the "else"-tag is displayed.
 * </output>
 *
 * <code title="inline notation">
 * {f:if(condition: someVariable, then: 'condition is met', else: 'condition is not met')}
 * </code>
 * <output>
 * The value of the "then" attribute is displayed if the variable evaluates to TRUE.
 * Otherwise, everything the value of the "else"-attribute is displayed.
 * </output>
 *
 * <code title="inline notation with comparison">
 * {f:if(condition: '{workspace} == {userWorkspace}', then: 'this is a user workspace', else: 'no user workspace')}
 * </code>
 * <output>
 * If the condition is not just a single variable, the whole expression must be enclosed in quotes and variables need
 * to be enclosed in curly braces.
 * </output>
 *
 * @see \TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::convertArgumentValue()
 * @api
 */
class IfViewHelper extends AbstractConditionViewHelper
{
    /**
     * Renders <f:then> child if $condition is true, otherwise renders <f:else> child.
     *
     * @param boolean $condition View helper condition
     * @return string the rendered string
     * @api
     */
    public function render($condition)
    {
        if ($condition) {
            return $this->renderThenChild();
        } else {
            return $this->renderElseChild();
        }
    }
}
