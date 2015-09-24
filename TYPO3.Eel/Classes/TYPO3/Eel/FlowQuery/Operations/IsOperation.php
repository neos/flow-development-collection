<?php
namespace TYPO3\Eel\FlowQuery\Operations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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
     * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the filter arguments
     * @return void|boolean
     */
    public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments)
    {
        if (count($arguments) == 0) {
            return count($flowQuery->getContext()) > 0;
        } else {
            $flowQuery->pushOperation('is', array());
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
