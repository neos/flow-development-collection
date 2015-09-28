<?php
namespace TYPO3\Eel\FlowQuery\Operations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;

/**
 * Slice the current context
 *
 * If no arguments are given, the full context is returned. Otherwise the
 * value contained in the context are sliced with offset and length.
 */
class SliceOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'slice';

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments A mandatory start and optional end index in the context, negative indices indicate an offset from the start or end respectively
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $context = $flowQuery->getContext();

        if (isset($arguments[0]) && isset($arguments[1])) {
            $context = array_slice($context, (integer)$arguments[0], (integer)$arguments[1] - (integer)$arguments[0]);
        } elseif (isset($arguments[0])) {
            $context = array_slice($context, (integer)$arguments[0]);
        }

        $flowQuery->setContext($context);
    }
}
