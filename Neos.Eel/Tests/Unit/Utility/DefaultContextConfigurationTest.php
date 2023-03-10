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


    /** @test */
    public function dotNestingNonOnNestedLevel()
    {
        $this->expectExceptionMessage("Path should not contain dots");

        $config = [
            "Foo" => [
                "Foo.Bar" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*"
                ],
            ],
        ];

        foreach (DefaultContextConfiguration::fromConfiguration($config)->toDefaultContextEntries() as $_) {
            continue;
        };
    }

    public function dotMergingProvider(): iterable
    {
        yield "single entry" => [
            "rawConfig" => ['Example' => ExampleProtectedContextAwareHelper::class],
            "normalized" => ['Example' => ExampleProtectedContextAwareHelper::class],
        ];

        yield "two dot paths" => [
            "rawConfig" => [
                "Foo.Buz" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*"
                ],
                "Foo.Bar" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*"
                ]
            ],
            "normalized" => [
                "Foo" => [
                    "Buz" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => "*"
                    ],
                    "Bar" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => "*"
                    ]
                ]
            ],
        ];

        yield "dot path with nested config" => [
            "rawConfig" => [
                "Foo" => [
                    "Buz" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => "*"
                    ]
                ],
                "Foo.Bar" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*"
                ]
            ],
            "normalized" => [
                "Foo" => [
                    "Buz" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => "*"
                    ],
                    "Bar" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => "*"
                    ]
                ]
            ],
        ];

        yield "dot paths and nested config with direct className" => [
            "rawConfig" => [
                "Foo" => [
                    "Bar" => [
                        "Example" => [
                            "className" => ExampleHelper::class,
                            "allowedMethods" => "*"
                        ],
                    ]
                ],
                'MyExample' => ExampleProtectedContextAwareHelper::class,
                'Foo.My.Example' => ExampleProtectedContextAwareHelper::class,
                'Example' => ExampleProtectedContextAwareHelper::class,
                'Foo.Bar.Example' => ExampleProtectedContextAwareHelper::class,
            ],
            "normalized" => [
                'Foo' => [
                    'Bar' => [
                        'Example' => ExampleProtectedContextAwareHelper::class,
                    ],
                    'My' => [
                        'Example' => ExampleProtectedContextAwareHelper::class,
                    ]
                ],
                'Example' => ExampleProtectedContextAwareHelper::class,
                'MyExample' => ExampleProtectedContextAwareHelper::class,
            ],
        ];

        yield "dot merging few2325235" => [
            "rawConfig" => [
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
            ],
            "normalized" => [
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
            ],
        ];

        yield "merging also illegal ambiguous at first" => [
            "rawConfig" => [
                "Foo" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*"
                ],
                "Foo.Bar" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*"
                ]
            ],
            "normalized" => [
                "Foo" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*",
                    "Bar" => [
                        "className" => ExampleHelper::class,
                        "allowedMethods" => "*"
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dotMergingProvider
     */
    public function normalizeFirstLevelDotPathsIntoNestedConfig(array $rawConfig, array $normalized)
    {
        self::assertEquals(
            $normalized,
            DefaultContextConfiguration::fromConfiguration($rawConfig)->getConfiguration()
        );
    }
}
