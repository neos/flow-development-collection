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
     * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the elements to add (as array in index 0)
     * @return void
     */
    public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments)
    {
        $output = array();
        foreach ($flowQuery->getContext() as $element) {
            $output[] = $element;
        }
        if (isset($arguments[0])) {
            foreach ($arguments[0] as $element) {
                $output[] = $element;
            }
        }
        $flowQuery->setContext($output);
    }
}
