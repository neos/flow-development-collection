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
use TYPO3\Fluid\Core\ViewHelper;

/**
 * This ViewHelper counts elements of the specified array or countable object.
 *
 * = Examples =
 *
 * <code title="Count array elements">
 * <f:count subject="{0:1, 1:2, 2:3, 3:4}" />
 * </code>
 * <output>
 * 4
 * </output>
 *
 * <code title="inline notation">
 * {objects -> f:count()}
 * </code>
 * <output>
 * 10 (depending on the number of items in {objects})
 * </output>
 *
 * @api
 */
class CountViewHelper extends AbstractViewHelper
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
     * Counts the items of a given property.
     *
     * @param array|\Countable $subject The array or \Countable to be counted
     * @return integer The number of elements
     * @throws ViewHelper\Exception
     * @api
     */
    public function render($subject = null)
    {
        if ($subject === null) {
            $subject = $this->renderChildren();
        }
        if (is_object($subject) && !$subject instanceof \Countable) {
            throw new ViewHelper\Exception('CountViewHelper only supports arrays and objects implementing \Countable interface. Given: "' . get_class($subject) . '"', 1279808078);
        }
        return count($subject);
    }
}
