<?php
namespace TYPO3\Flow\Tests\Unit\Object\DependencyInject;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Object\DependencyInjection\DependencyProxy;
use TYPO3\Flow\Tests\UnitTestCase;

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
