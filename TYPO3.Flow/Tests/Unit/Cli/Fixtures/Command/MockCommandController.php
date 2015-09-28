<?php
namespace TYPO3\Flow\Tests\Unit\Cli\Fixtures\Command;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A mock CLI Command
 */
class MockACommandController extends \TYPO3\Flow\Cli\Command
{
    public function fooCommand()
    {
    }

    public function barCommand($someArgument)
    {
    }
}

/**
 * Another mock CLI Command
 */
class MockBCommandController extends \TYPO3\Flow\Cli\Command
{
    public function bazCommand()
    {
    }
}
