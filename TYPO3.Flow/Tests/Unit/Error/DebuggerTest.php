<?php
namespace TYPO3\Flow\Tests\Unit\Error;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Error\Debugger;

/**
 * Testcase for the Debugger
 *
 */
class DebuggerTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        Debugger::renderDump(function () {}, 0);
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
        $this->assertContains('TYPO3\\\\Flow\\\\Core\\\\.*', $ignoredClassesRegex);
    }

    /**
     * @test
     */
    public function ignoredClassesAreNotRendered()
    {
        $object = new ApplicationContext('Development');
        $this->assertEquals('TYPO3\Flow\Core\ApplicationContext object', Debugger::renderDump($object, 10, true));
    }
}
