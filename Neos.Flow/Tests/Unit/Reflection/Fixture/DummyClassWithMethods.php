<?php
namespace Neos\Flow\Tests\Reflection\Fixture;

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
 * Dummy class for the Reflection tests
 *
 */
class DummyClassWithMethods
{
    /**
     * Some method
     *
     * @firsttag
     * @secondtag a
     * @secondtag b
     * @param string $arg1 Argument 1 documentation
     * @return void
     */
    public function firstMethod($arg1, &$arg2, \stdClass $arg3, $arg4 = 'default')
    {
    }

    /**
     * Some method
     *
     * @return void
     */
    protected function secondMethod()
    {
    }
}
