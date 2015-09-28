<?php
namespace TYPO3\Flow\Tests\Unit\Object\DependencyInject;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Object\DependencyInjection\DependencyProxy;

/**
 *
 */
class DependencyProxyTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getClassNameReturnsTheNameOfTheProxiedDependencyClass()
    {
        $proxy = new DependencyProxy('SomeClass', function () {});
        $this->assertSame('SomeClass', $proxy->_getClassName());
    }
}
