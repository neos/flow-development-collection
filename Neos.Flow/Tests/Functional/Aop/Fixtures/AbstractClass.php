<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


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
        return 'foo: ' . $foo;
    }
}
