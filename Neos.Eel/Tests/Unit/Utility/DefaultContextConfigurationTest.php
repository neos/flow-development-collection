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
use Neos\Eel\Utility\DefaultContextConfiguration;
use Neos\Eel\Utility\EelHelperDefaultContextEntry;
use Neos\Flow\Tests\UnitTestCase;

class DefaultContextConfigurationTest extends UnitTestCase
{
    /** @test */
    public function toDefaultContextEntries()
    {
        $defaultContextConfiguration = DefaultContextConfiguration::fromConfiguration([
            "Example" => [
                "className" => ExampleHelper::class,
                "allowedMethods" => "*"
            ],
            "Foo" => [
                "Bar" => [
                    "Example" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => [
                            "methodA",
                            "methodB"
                        ]
                    ],
                ]
            ],
            "__internalLegacyConfig" => [
                'Example' => ExampleProtectedContextAwareHelper::class,
                'Foo.Bar.Example' => ExampleProtectedContextAwareHelper::class
            ]
        ]);

        $output = [];
        foreach ($defaultContextConfiguration->toDefaultContextEntries() as $contextEntry) {
            $output[] = $contextEntry;
        }

        self::assertEquals(
            $output,
            [
                new EelHelperDefaultContextEntry(
                    ["Example"],
                    ExampleHelper::class,
                    ["*"]
                ),
                new EelHelperDefaultContextEntry(
                    ["Foo", "Bar", "Example"],
                    ExampleHelper::class,
                    ["methodA", "methodB"]
                )
            ]
        );
    }

    /** @test */
    public function ambiguousNesting()
    {
        $this->expectExceptionMessage("Cannot use namespace 'Foo' as helper with nested helpers.");

        $config = [
            "Example" => [
                "className" => ExampleHelper::class,
                "allowedMethods" => "*"
            ],
            "Foo" => [
                "Bar" => [
                    "Example" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => "*"
                    ],
                ],
                "className" => ExampleHelper::class,
                "allowedMethods" => "*"
            ]
        ];

        foreach (DefaultContextConfiguration::fromConfiguration($config)->toDefaultContextEntries() as $_) {
            continue;
        };
    }

    /** @test */
    public function ambiguousNestingWithDotIndex()
    {
        $this->expectExceptionMessage("Cannot use namespace 'Foo' as helper with nested helpers.");

        $config = [
            "Foo" => [
                "className" => ExampleHelper::class,
                "allowedMethods" => "*"
            ],
            "Foo.Bar" => [
                "className" => ExampleHelper::class,
                "allowedMethods" => "*"
            ]
        ];

        foreach (DefaultContextConfiguration::fromConfiguration($config)->toDefaultContextEntries() as $_) {
            continue;
        };
    }
}
