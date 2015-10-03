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
 * This ViewHelper cycles through the specified values.
 * This can be often used to specify CSS classes for example.
 * **Note:** To achieve the "zebra class" effect in a loop you can also use the "iteration" argument of the **for** ViewHelper.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo"><f:cycle values="{0: 'foo', 1: 'bar', 2: 'baz'}" as="cycle">{cycle}</f:cycle></f:for>
 * </code>
 * <output>
 * foobarbazfoo
 * </output>
 *
 * <code title="Alternating CSS class">
 * <ul>
 *   <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">
 *     <f:cycle values="{0: 'odd', 1: 'even'}" as="zebraClass">
 *       <li class="{zebraClass}">{foo}</li>
 *     </f:cycle>
 *   </f:for>
 * </ul>
 * </code>
 * <output>
 * <ul>
 *   <li class="odd">1</li>
 *   <li class="even">2</li>
 *   <li class="odd">3</li>
 *   <li class="even">4</li>
 * </ul>
 * </output>
 *
 * Note: The above examples could also be achieved using the "iteration" argument of the ForViewHelper
 *
 * @api
 */
class CycleViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * The values to be iterated through
     *
     * @var array|\SplObjectStorage
     */
    protected $values = null;

    /**
     * Current values index
     *
     * @var integer
     */
    protected $currentCycleIndex = null;

    /**
     * Renders cycle view helper
     *
     * @param array $values The array or object implementing \ArrayAccess (for example \SplObjectStorage) to iterated over
     * @param string $as The name of the iteration variable
     * @return string Rendered result
     * @api
     */
    public function render($values, $as)
    {
        if ($values === null) {
            return $this->renderChildren();
        }
        if ($this->values === null) {
            $this->initializeValues($values);
        }
        if ($this->currentCycleIndex === null || $this->currentCycleIndex >= count($this->values)) {
            $this->currentCycleIndex = 0;
        }

        $currentValue = isset($this->values[$this->currentCycleIndex]) ? $this->values[$this->currentCycleIndex] : null;
        $this->templateVariableContainer->add($as, $currentValue);
        $output = $this->renderChildren();
        $this->templateVariableContainer->remove($as);

        $this->currentCycleIndex++;

        return $output;
    }

    /**
     * Sets this->values to the current values argument and resets $this->currentCycleIndex.
     *
     * @param array|\Traversable $values The array or \SplObjectStorage to be stored in $this->values
     * @return void
     * @throws ViewHelper\Exception
     */
    protected function initializeValues($values)
    {
        if (is_object($values)) {
            if (!$values instanceof \Traversable) {
                throw new ViewHelper\Exception('CycleViewHelper only supports arrays and objects implementing \Traversable interface', 1248728393);
            }
            $this->values = iterator_to_array($values, false);
        } else {
            $this->values = array_values($values);
        }
        $this->currentCycleIndex = 0;
    }
}
