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

use Neos\Eel\Tests\Unit\Utility\Fixtures\ExampleStaticFactoryFunction;
use Neos\Eel\Utility\EelFunctionDefaultContextEntry;
use Neos\Flow\Tests\UnitTestCase;

class EelFunctionDefaultContextEntryTest extends UnitTestCase
{
    /** @test */
    public function eelFunctionDefaultContextEntry()
    {
        $ctx = new EelFunctionDefaultContextEntry(
            ["exampleFunction"],
            ExampleStaticFactoryFunction::class . "::exampleStaticFunction",
        );

        $contextValue = $ctx->toContextValue();
        self::assertEquals(["exampleFunction"], $ctx->getPath());
        self::assertInstanceOf(\Closure::class, $contextValue);
        self::assertEquals(json_encode(['exampleStaticFunction' => ['arg1', 2]]), $contextValue('arg1', 2));
        self::assertEquals([["exampleFunction"]], $ctx->getAllowedMethods());
    }

    /** @test */
    public function functionHelpersAreOnlyAllowedOnRootLevel()
    {
        $this->expectExceptionMessage('Function helpers are only allowed on root level, "Example.foo" was given');
        new EelFunctionDefaultContextEntry(
            ["Example", "foo"],
            ExampleStaticFactoryFunction::class . "::exampleStaticFunction",
        );
    }
}
