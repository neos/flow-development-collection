<?php
namespace Neos\Eel\FlowQuery\Operations;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Removes the given items from the current context.
 * The operation accepts one argument that may be an Array, a FlowQuery
 * or an Object.
 */
class RemoveOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'remove';

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the elements to remove (as array in index 0)
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $valuesToRemove = [];
        if (isset($arguments[0])) {
            if (is_array($arguments[0])) {
                $valuesToRemove = $arguments[0];
            } elseif ($arguments[0] instanceof \Traversable) {
                $valuesToRemove = iterator_to_array($arguments[0]);
            } else {
                $valuesToRemove[] = $arguments[0];
            }
        }
        $filteredContext = array_filter(
            $flowQuery->getContext(),
            function ($item) use ($valuesToRemove) {
                return in_array($item, $valuesToRemove, true) === false;
            }
        );
        $flowQuery->setContext($filteredContext);
    }
}
