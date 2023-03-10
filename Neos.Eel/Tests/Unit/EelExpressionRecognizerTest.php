<?php

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

class EelExpressionRecognizerTest extends \Neos\Flow\Tests\UnitTestCase
{
    public function wrappedEelExpressionProvider()
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

    /**
     * @test
     * @dataProvider wrappedEelExpressionProvider
     */
    public function unwrapEelExpression(string $wrapped, string $unwrapped)
    {
        self::assertEquals(
            Utility::parseEelExpression($wrapped),
            $unwrapped
        );
    }

    public function notAnExpressionProvider()
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

    /**
     * @test
     * @dataProvider notAnExpressionProvider
     */
    public function notAnExpression(string $expression)
    {
        self::assertNull(
            Utility::parseEelExpression($expression)
        );
    }

    /** @test */
    public function leftOpenEelDoesntResultInCatastrophicBacktracking()
    {
        $malformedExpression = '${abc abc abc abc abc abc abc abc abc abc abc ...';
        $return = preg_match(Package::EelExpressionRecognizer, $malformedExpression);
        self::assertNotSame(false, $return, "Regex not efficient");
        self::assertEquals($return, 0, "Regex should not match");
    }
}
