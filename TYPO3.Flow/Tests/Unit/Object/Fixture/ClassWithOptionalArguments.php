<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Fixture class for various unit tests (mainly the package- and object
 * manager)
 *
 */
class ClassWithOptionalArguments
{
    public $argument1;
    public $argument2;
    public $argument3;

    /**
     * Dummy constructor which accepts up to three arguments
     *
     * @param mixed $argument1
     * @param mixed $argument2
     * @param mixed $argument3
     */
    public function __construct($argument1 = null, $argument2 = null, $argument3 = null)
    {
        $this->argument1 = $argument1;
        $this->argument2 = $argument2;
        $this->argument3 = $argument3;
    }
}
