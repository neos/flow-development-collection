<?php
namespace Neos\Flow\Tests\Unit\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Debugger
 */
class DebuggerTest extends UnitTestCase
{
    public function setUp()
    {
        Debugger::clearState();
    }

    /**
     * @test
     */
    public function renderingClosuresWorksWithoutThrowingException()
    {
        Debugger::renderDump(function () {
        }, 0);
        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function considersProxyClassWhenIsProxyPropertyIsPresent()
    {
        $object = new \stdClass();
        $object->__IS_PROXY__ = true;
        $this->assertRegExp('/\sclass=\"debug\-proxy\"/', Debugger::renderDump($object, 0, false));
    }

    /**
     * @test
     */
    public function ignoredClassesRegexContainsFallback()
    {
        $ignoredClassesRegex = Debugger::getIgnoredClassesRegex();
        $this->assertContains('Neos\\\\Flow\\\\Core\\\\.*', $ignoredClassesRegex);
    }

    /**
     * @test
     */
    public function ignoredClassesAreNotRendered()
    {
        $object = new ApplicationContext('Development');
        $this->assertEquals('Neos\Flow\Core\ApplicationContext object', Debugger::renderDump($object, 10, true));
    }
}
