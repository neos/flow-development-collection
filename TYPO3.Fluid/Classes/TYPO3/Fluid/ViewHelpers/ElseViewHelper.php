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

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Else-Branch of a condition. Only has an effect inside of "If". See the If-ViewHelper for documentation.
 *
 * = Examples =
 *
 * <code title="Output content if condition is not met">
 * <f:if condition="{someCondition}">
 *   <f:else>
 *     condition was not true
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Everything inside the "else" tag is displayed if the condition evaluates to FALSE.
 * Otherwise nothing is outputted in this example.
 * </output>
 *
 * @see TYPO3\Fluid\ViewHelpers\IfViewHelper
 * @api
 */
class ElseViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @return string the rendered string
     * @api
     */
    public function render()
    {
        return $this->renderChildren();
    }
}
