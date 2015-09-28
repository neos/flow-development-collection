<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * A sub class of the abstract class
 *
 */
class SubClassOfAbstractClass extends AbstractClass
{
    /**
     * @param $foo
     * @return string
     */
    public function abstractMethod($foo)
    {
        return "foo: $foo";
    }
}
