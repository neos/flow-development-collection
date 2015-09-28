<?php
namespace TYPO3\Eel\Tests\Functional\FlowQuery\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

class ExampleNonFinalOperation extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation
{
    protected static $shortName = 'exampleNonFinalOperation';
    protected static $final = false;

    public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $query, array $arguments)
    {
    }
}
