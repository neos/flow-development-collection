<?php
namespace Neos\FluidAdaptor\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper that outputs its child nodes with \Neos\Flow\var_dump()
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
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('title', 'string', 'The title', false, null);
        $this->registerArgument('typeOnly', 'boolean', 'Whether only the type should be returned instead of the whole chain.', false, false);
    }

    /**
     * Wrapper for \Neos\Flow\var_dump()
     *
     * @return string debug string
     */
    public function render()
    {
        $expressionToExamine = $this->renderChildren();
        if ($this->arguments['typeOnly'] === true && $expressionToExamine !== null) {
            $expressionToExamine = (is_object($expressionToExamine) ? get_class($expressionToExamine) : gettype($expressionToExamine));
        }

        return \Neos\Flow\var_dump($expressionToExamine, $this->arguments['title'], true);
    }
}
