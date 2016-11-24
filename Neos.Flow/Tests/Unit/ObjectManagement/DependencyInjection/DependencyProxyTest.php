<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\DependencyInject;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Tests\UnitTestCase;

class DependencyProxyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getClassNameReturnsTheNameOfTheProxiedDependencyClass()
    {
        $proxy = new DependencyProxy('SomeClass', function () {
        });
        $this->assertSame('SomeClass', $proxy->_getClassName());
    }
}
