<?php
namespace TYPO3\Eel\Tests\Functional\FlowQuery\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

class ExampleFinalOperationWithHigherPriority extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation
{
    protected static $shortName = 'exampleFinalOperation';
    protected static $final = true;

    protected static $priority = 100;

    public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $query, array $arguments)
    {
        return 'Priority 100';
    }
}
