<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Eel\Package;
use Neos\Eel\Utility;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class EelExpressionRecognizerTest extends UnitTestCase
{
    public static function wrappedEelExpressionProvider(): \Generator
    {
        yield "simple" => [
            "wrapped" => '${foo + bar}',
            "unwrapped" => 'foo + bar',
        ];

        yield "string" => [
            "wrapped" => '${"foo" + bar}',
            "unwrapped" => '"foo" + bar',
        ];

        yield "string with escaping and special chars" => [
            "wrapped" => <<<'EEL'
            ${"fo\"o{" + bar}
            EEL,
            "unwrapped" => <<<'EEL'
            "fo\"o{" + bar
            EEL,
        ];

        yield "nested object" => [
            "wrapped" => <<<'EEL'
            ${{foo: {hi: "lol"}}}
            EEL,
            "unwrapped" => <<<'EEL'
            {foo: {hi: "lol"}}
            EEL,
        ];
    }

    #[DataProvider('wrappedEelExpressionProvider')]
    #[Test]
    public function unwrapEelExpression(string $wrapped, string $unwrapped): void
    {
        self::assertEquals(
            Utility::parseEelExpression($wrapped),
            $unwrapped
        );
    }

    public static function notAnExpressionProvider(): \Generator
    {
        yield "missing object brace" => [
            '${{foo: {}}',
        ];

        yield "left open string" => [
            '${"foo + bar}',
        ];

        yield "space on start" => [
            '   ${foo + bar}',
        ];

        yield "space on end" => [
            '${foo + bar}   ',
        ];

        yield "unwrapped" => [
            'foo + bar',
        ];
    }

    #[DataProvider('notAnExpressionProvider')]
    #[Test]
    public function notAnExpression(string $expression): void
    {
        self::assertNull(
            Utility::parseEelExpression($expression)
        );
    }

    #[Test]
    public function leftOpenEelDoesntResultInCatastrophicBacktracking(): void
    {
        $malformedExpression = '${abc abc abc abc abc abc abc abc abc abc abc ...';
        $return = preg_match(Package::EelExpressionRecognizer, $malformedExpression);
        self::assertNotFalse($return, "Regex not efficient");
        self::assertSame(0, $return, "Regex should not match");
    }
}
