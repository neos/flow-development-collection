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
 * Removes duplicate items from the current context.
 */
class UniqueOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'unique';

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the elements to remove (as array in index 0)
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $context = $flowQuery->getContext();
        if ($context instanceof \Traversable) {
            $context = iterator_to_array($context);
        }
        $flowQuery->setContext(array_unique($context));
    }
}
