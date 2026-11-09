<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Exception;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Bootstrap class
 */
final class BootstrapTest extends UnitTestCase
{
    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function commandIdentifiersAndCompiletimeControllerInfo(): \Iterator
    {
        yield [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'neos.flow:core:shell', true];
        yield [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'flow:core:shell', true];
        yield [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'core:shell', false];
        yield [['neos.flow:core:*', 'neos.flow:cache:flush'], 'neos.flow:core:shell', true];
        yield [['neos.flow:core:*', 'neos.flow:cache:flush'], 'flow:core:shell', true];
        yield [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'neos.flow:help:help', false];
        yield [['neos.flow:core:*', 'neos.flow:cache:*'], 'flow:cache:flush', true];
        yield [['neos.flow:core:*', 'neos.flow:cache:*'], 'flow5:core:shell', false];
        yield [['neos.flow:core:*', 'neos.flow:cache:*'], 'typo3:core:shell', false];
    }

    #[DataProvider('commandIdentifiersAndCompiletimeControllerInfo')]
    #[Test]
    public function isCompileTimeCommandControllerChecksIfTheGivenCommandIdentifierRefersToACompileTimeController($compiletimeCommandControllerIdentifiers, $givenCommandIdentifier, $expectedResult)
    {
        $bootstrap = new Bootstrap('Testing');
        foreach ($compiletimeCommandControllerIdentifiers as $compiletimeCommandControllerIdentifier) {
            $bootstrap->registerCompiletimeCommand($compiletimeCommandControllerIdentifier);
        }

        self::assertSame($expectedResult, $bootstrap->isCompiletimeCommand($givenCommandIdentifier));
    }

    #[Test]
    public function resolveRequestHandlerThrowsUsefulExceptionIfNoRequestHandlerFound()
    {
        $this->expectException(Exception::class);
        $bootstrap = $this->getAccessibleMock(Bootstrap::class, [], [], '', false);
        $bootstrap->_call('resolveRequestHandler');
    }
}
