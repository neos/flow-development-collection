<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * An abstract class with an abstract and a concrete method
 *
 */
abstract class AbstractClass
{
    /**
     * @param $foo
     * @return string
     */
    abstract public function abstractMethod($foo);

    /**
     * @param $foo
     * @return string
     */
    public function concreteMethod($foo)
    {
        return "foo: $foo";
    }
}
