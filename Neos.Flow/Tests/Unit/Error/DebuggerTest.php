<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Debugger
 */
final class DebuggerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        Debugger::clearState();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function renderingClosuresWorksWithoutThrowingException()
    {
        Debugger::renderDump(function () {
        }, 0);
    }

    #[Test]
    public function considersProxyClassWhenIsProxyPropertyIsPresent()
    {
        $object = new \stdClass();
        $object->__IS_PROXY__ = true;
        self::assertMatchesRegularExpression('/\sclass=\"debug\-proxy\"/', Debugger::renderDump($object, 0, false));
    }

    #[Test]
    public function ignoredClassesRegexContainsFallback()
    {
        $ignoredClassesRegex = Debugger::getIgnoredClassesRegex();
        self::assertStringContainsString('Neos\\\\Flow\\\\Core\\\\.*', $ignoredClassesRegex);
    }

    #[Test]
    public function ignoredClassesAreNotRendered()
    {
        $object = new ApplicationContext('Development');
        self::assertSame('Neos\Flow\Core\ApplicationContext object', Debugger::renderDump($object, 0, true));
    }

    #[Test]
    public function uninitializedTypedPropertiesAreNotAccessed()
    {
        // if the test fails, an exception raises an error, no assertion needed
        $this->expectNotToPerformAssertions();

        $className = 'TestClass' . md5(uniqid((string)mt_rand(), true));
        eval('
            class ' . $className . ' {
                public string $stringProperty;
            }
        ');
        $object = new $className();
        Debugger::renderDump($object, 1, true);
    }
}
