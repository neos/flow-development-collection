<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Log\Utility;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class LogEnvironmentTest extends FunctionalTestCase
{
    public static function fromMethodNameDataProvider(): \Iterator
    {
        yield 'packageKeyCanBeDetermined' => [
            'method' => __METHOD__,
            'expected' => [
                'FLOW_LOG_ENVIRONMENT' => [
                    'packageKey' => 'Neos.Flow',
                    'className' => 'Neos\Flow\Tests\Functional\Log\Utility\LogEnvironmentTest',
                    'methodName' => 'fromMethodNameDataProvider'
                ]
            ]
        ];
        yield 'unknownPackageKeyReturnsFirstPart' => [
            'method' => 'Some\Unknown\CLass\Path::methodName',
            'expected' => [
                'FLOW_LOG_ENVIRONMENT' => [
                    'packageKey' => 'Some',
                    'className' => 'Some\Unknown\CLass\Path',
                    'methodName' => 'methodName'
                ]
            ]
        ];
    }


    #[DataProvider('fromMethodNameDataProvider')]
    #[Test]
    public function fromMethodName(string $method, array $expected): void
    {
        $actual = LogEnvironment::fromMethodName($method);
        self::assertEquals($expected, $actual);
    }
}
