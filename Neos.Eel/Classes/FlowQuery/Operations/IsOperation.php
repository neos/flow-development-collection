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
 * Check whether the at least one of the context elements match the given filter.
 *
 * Without arguments is evaluates to TRUE if the context is not empty. If arguments
 * are given, they are used to filter the context before evaluation.
 */
class IsOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'is';

    /**
     * {@inheritdoc}
     *
     * @var boolean
     */
    protected static $final = true;

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the filter arguments
     * @return void|boolean
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (count($arguments) == 0) {
            return count($flowQuery->getContext()) > 0;
        } else {
            $flowQuery->pushOperation('is', []);
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
