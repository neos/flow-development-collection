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
    protected function setUp(): void
    {
        Debugger::clearState();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function renderingClosuresWorksWithoutThrowingException()
    {
        Debugger::renderDump(function () {
        }, 0);
    }

    /**
     * @test
     */
    public function considersProxyClassWhenIsProxyPropertyIsPresent()
    {
        $object = new \stdClass();
        $object->__IS_PROXY__ = true;
        self::assertMatchesRegularExpression('/\sclass=\"debug\-proxy\"/', Debugger::renderDump($object, 0, false));
    }

    /**
     * @test
     */
    public function ignoredClassesRegexContainsFallback()
    {
        $ignoredClassesRegex = Debugger::getIgnoredClassesRegex();
        self::assertStringContainsString('Neos\\\\Flow\\\\Core\\\\.*', $ignoredClassesRegex);
    }

    /**
     * @test
     */
    public function ignoredClassesAreNotRendered()
    {
        $object = new ApplicationContext('Development');
        self::assertEquals('Neos\Flow\Core\ApplicationContext object', Debugger::renderDump($object, 0, true));
    }

    /**
     * @test
     */
    public function uninitializedTypedPropertiesAreNotAccessed()
    {
        if (PHP_VERSION_ID < 70400) {
            self::markTestSkipped('Test only works on PHP 7.4 and above');
        }
        // if the test fails, an exception raises an error, no assertion needed
        $this->expectNotToPerformAssertions();

        $className = 'TestClass' . md5(uniqid(mt_rand(), true));
        eval('
            class ' . $className . ' {
                public string $stringProperty;
            }
        ');
        $object = new $className();
        Debugger::renderDump($object, 1, true);
    }
}
