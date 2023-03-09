<?php

namespace Neos\Eel\Tests\Unit\Utility;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\Tests\Unit\Utility\Fixtures\ExampleHelper;
use Neos\Eel\Tests\Unit\Utility\Fixtures\ExampleProtectedContextAwareHelper;
use Neos\Eel\Utility\EelHelperDefaultContextEntry;
use Neos\Flow\Tests\UnitTestCase;

class EelHelperDefaultContextEntryTest extends UnitTestCase
{
    /** @test */
    public function lol()
    {
        $ctx = new EelHelperDefaultContextEntry(
            ["Example"],
            ExampleHelper::class,
            ["*"]
        );

        self::assertEquals(["Example"], $ctx->paths);
        self::assertEquals(new ExampleHelper(), $ctx->toContextValue());
        self::assertEquals(["Example.*"], $ctx->getAllowedMethods());
    }

    /** @test */
    public function shouldNotCombineProtectedContextAwareInterfaceWithAllowedMethods()
    {
        $this->expectExceptionMessage("EEL Helper '" . ExampleProtectedContextAwareHelper::class . "' should not implement ProtectedContextAwareInterface and have allowedMethods configured.");
        new EelHelperDefaultContextEntry(
            ["Example"],
            ExampleProtectedContextAwareHelper::class,
            ["*"]
        );
    }


    /** @test */
    public function shouldNotConfigurePlainHelperWithoutAllowedMethodsOrProtectedContextAwareInterface()
    {
        $this->expectExceptionMessage("Plain Helper '" . ExampleHelper::class . "' should have allowedMethods or ProtectedContextAwareInterface configured.");
        new EelHelperDefaultContextEntry(
            ["Example"],
            ExampleHelper::class,
            []
        );
    }
}
