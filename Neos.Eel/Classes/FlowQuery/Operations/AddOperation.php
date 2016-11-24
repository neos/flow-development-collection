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
use Neos\Flow\Annotations as Flow;

/**
 * Add another $flowQuery object to the current one.
 */
class AddOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'add';

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the elements to add (as array in index 0)
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        foreach ($flowQuery->getContext() as $element) {
            $output[] = $element;
        }
        if (isset($arguments[0])) {
            if (is_array($arguments[0]) || $arguments[0] instanceof \Traversable) {
                foreach ($arguments[0] as $element) {
                    $output[] = $element;
                }
            } else {
                $output[] = $arguments[0];
            }
        }
        $flowQuery->setContext($output);
    }
}
