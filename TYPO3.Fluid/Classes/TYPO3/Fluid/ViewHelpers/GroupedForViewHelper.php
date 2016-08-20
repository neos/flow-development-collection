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

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper;

/**
 * Grouped loop view helper.
 * Loops through the specified values.
 *
 * The groupBy argument also supports property paths.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color">
 *   <f:for each="{fruitsOfThisColor}" as="fruit">
 *     {fruit.name}
 *   </f:for>
 * </f:groupedFor>
 * </code>
 * <output>
 * apple cherry strawberry banana
 * </output>
 *
 * <code title="Two dimensional list">
 * <ul>
 *   <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color" groupKey="color">
 *     <li>
 *       {color} fruits:
 *       <ul>
 *         <f:for each="{fruitsOfThisColor}" as="fruit" key="label">
 *           <li>{label}: {fruit.name}</li>
 *         </f:for>
 *       </ul>
 *     </li>
 *   </f:groupedFor>
 * </ul>
 * </code>
 * <output>
 * <ul>
 *   <li>green fruits
 *     <ul>
 *       <li>0: apple</li>
 *     </ul>
 *   </li>
 *   <li>red fruits
 *     <ul>
 *       <li>1: cherry</li>
 *     </ul>
 *     <ul>
 *       <li>3: strawberry</li>
 *     </ul>
 *   </li>
 *   <li>yellow fruits
 *     <ul>
 *       <li>2: banana</li>
 *     </ul>
 *   </li>
 * </ul>
 * </output>
 *
 * Note: Using this view helper can be a sign of weak architecture. If you end up using it extensively
 * you might want to fine-tune your "view model" (the data you assign to the view).
 *
 * @api
 */
class GroupedForViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Iterates through elements of $each and renders child nodes
     *
     * @param array $each The array or \SplObjectStorage to iterated over
     * @param string $as The name of the iteration variable
     * @param string $groupBy Group by this property
     * @param string $groupKey The name of the variable to store the current group
     * @return string Rendered string
     * @throws ViewHelper\Exception
     * @api
     */
    public function render($each, $as, $groupBy, $groupKey = 'groupKey')
    {
        $output = '';
        if ($each === null) {
            return '';
        }
        if (is_object($each)) {
            if (!$each instanceof \Traversable) {
                throw new ViewHelper\Exception('GroupedForViewHelper only supports arrays and objects implementing \Traversable interface', 1253108907);
            }
            $each = iterator_to_array($each);
        }

        $groups = $this->groupElements($each, $groupBy);

        foreach ($groups['values'] as $currentGroupIndex => $group) {
            $this->templateVariableContainer->add($groupKey, $groups['keys'][$currentGroupIndex]);
            $this->templateVariableContainer->add($as, $group);
            $output .= $this->renderChildren();
            $this->templateVariableContainer->remove($groupKey);
            $this->templateVariableContainer->remove($as);
        }
        return $output;
    }

    /**
     * Groups the given array by the specified groupBy property.
     *
     * @param array $elements The array / traversable object to be grouped
     * @param string $groupBy Group by this property
     * @return array The grouped array in the form array('keys' => array('key1' => [key1value], 'key2' => [key2value], ...), 'values' => array('key1' => array([key1value] => [element1]), ...), ...)
     * @throws ViewHelper\Exception
     */
    protected function groupElements(array $elements, $groupBy)
    {
        $groups = array('keys' => array(), 'values' => array());
        foreach ($elements as $key => $value) {
            if (is_array($value)) {
                $currentGroupIndex = isset($value[$groupBy]) ? $value[$groupBy] : null;
            } elseif (is_object($value)) {
                $currentGroupIndex = ObjectAccess::getPropertyPath($value, $groupBy);
            } else {
                throw new ViewHelper\Exception('GroupedForViewHelper only supports multi-dimensional arrays and objects', 1253120365);
            }
            $currentGroupKeyValue = $currentGroupIndex;
            if ($currentGroupIndex instanceof \DateTimeInterface) {
                $currentGroupIndex = $currentGroupIndex->format(\DateTime::RFC850);
            } elseif (is_object($currentGroupIndex)) {
                $currentGroupIndex = spl_object_hash($currentGroupIndex);
            }
            $groups['keys'][$currentGroupIndex] = $currentGroupKeyValue;
            $groups['values'][$currentGroupIndex][$key] = $value;
        }
        return $groups;
    }
}
