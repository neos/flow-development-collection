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
 * View helper that outputs its child nodes with \TYPO3\Flow\var_dump()
 *
 * = Examples =
 *
 * <code>
 * <f:debug>{object}</f:debug>
 * </code>
 * <output>
 * all properties of {object} nicely highlighted
 * </output>
 *
 * <code title="inline notation and custom title">
 * {object -> f:debug(title: 'Custom title')}
 * </code>
 * <output>
 * all properties of {object} nicely highlighted (with custom title)
 * </output>
 *
 * <code title="only output the type">
 * {object -> f:debug(typeOnly: true)}
 * </code>
 * <output>
 * the type or class name of {object}
 * </output>
 *
 * Note: This view helper is only meant to be used during development
 *
 * @api
 */
class DebugViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Wrapper for \TYPO3\Flow\var_dump()
     *
     * @param string $title
     * @param boolean $typeOnly Whether only the type should be returned instead of the whole chain.
     * @return string debug string
     */
    public function render($title = null, $typeOnly = false)
    {
        $expressionToExamine = $this->renderChildren();
        if ($typeOnly === true && $expressionToExamine !== null) {
            $expressionToExamine = (is_object($expressionToExamine) ? get_class($expressionToExamine) : gettype($expressionToExamine));
        }

        ob_start();
        \TYPO3\Flow\var_dump($expressionToExamine, $title);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}
