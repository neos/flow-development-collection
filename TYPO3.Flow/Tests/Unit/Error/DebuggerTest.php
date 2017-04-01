<?php
namespace TYPO3\Flow\Tests\Unit\Error;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Error\Debugger;

/**
 * Testcase for the Debugger
 *
 */
class DebuggerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
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
}
