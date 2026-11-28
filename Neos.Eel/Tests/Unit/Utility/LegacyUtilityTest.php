<?php

namespace Neos\Eel\Tests\Unit\Utility;

use Neos\Eel\Tests\Unit\UncachedTestingEvaluatorTrait;
use Neos\Eel\Tests\Unit\Utility\Fixtures\ExampleHelper;
use Neos\Eel\Tests\Unit\Utility\Fixtures\ExampleProtectedContextAwareHelper;
use Neos\Eel\Tests\Unit\Utility\Fixtures\ExampleStaticFactoryFunction;
use Neos\Eel\Utility;
use Neos\Flow\Tests\UnitTestCase;

class LegacyUtilityTest extends UnitTestCase
{
    use UncachedTestingEvaluatorTrait;

    public function fixtureProvider(): iterable
    {
        yield 'singleProtectedContext' => [
            'defaultContextConfiguration' => ['Example' => ExampleProtectedContextAwareHelper::class],
            'expectedResult' => ['Example' => new ExampleProtectedContextAwareHelper()]
        ];

        yield 'multipleNestingProtectedContext' => [
            'defaultContextConfiguration' => ['Foo.Bar.Example' => ExampleProtectedContextAwareHelper::class],
            'expectedResult' => [
                'Foo' => [
                    'Bar' => [
                        'Example' => new ExampleProtectedContextAwareHelper()
                    ]
                ]
            ]
        ];

        yield 'multipleValidKeyProtectedContext' => [
            'defaultContextConfiguration' => [
                'Example' => ExampleProtectedContextAwareHelper::class,
                'Foo.Bar.Example' => ExampleProtectedContextAwareHelper::class
            ],
            'expectedResult' => [
                'Example' => new ExampleProtectedContextAwareHelper(),
                'Foo' => [
                    'Bar' => [
                        'Example' => new ExampleProtectedContextAwareHelper()
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider fixtureProvider
     */
    public function protectedContext(array $defaultContextConfiguration, array $expectedResult): void
    {
        $defaultContext = Utility::getDefaultContextVariables($defaultContextConfiguration);

        self::assertEquals($expectedResult, $defaultContext);
    }

    /** @test */
    public function callableStaticFactoryFunction(): void
    {
        $defaultContextConfiguration = ['example' => ExampleStaticFactoryFunction::class . '::exampleStaticFunction'];

        $defaultContext = Utility::getDefaultContextVariables($defaultContextConfiguration);

        self::assertEquals(['example'], array_keys($defaultContext));
        self::assertIsCallable($defaultContext['example']);
        self::assertEquals(json_encode(['exampleStaticFunction' => ['arg1', 2]]), $defaultContext['example']('arg1', 2));
    }

    /** @test */
    public function singleNestingThrowsException(): void
    {
        $defaultContextConfiguration = ['Foo.example' => ExampleStaticFactoryFunction::class . '::exampleStaticFunction'];

        $this->expectException(\Neos\Eel\Exception::class);
        $this->expectExceptionMessage('Function helpers are only allowed on root level, "Foo.example" was given');
        Utility::getDefaultContextVariables($defaultContextConfiguration);
    }

    public function defaultContextConfigurationProvider(): iterable
    {
        yield 'Nested configuration with className and allowedMethods' => [
            'expression' => '${Example.exampleFunction("foo", 42)}',
            'contextVariables' => [],
            'expectedResult' => '{"ExampleHelper::exampleFunction":["foo",42]}',
            'defaultContext' => [
                "Example" => [
                    "className" => ExampleHelper::class,
                    "allowedMethods" => "*"
                ]
            ],

        ];
    }

    /**
     * @test
     * @dataProvider defaultContextConfigurationProvider
     */
    public function defaultContextConfiguration(string $expression, array $contextVariables, mixed $expectedResult, array $defaultContext): void
    {
        $compilingEvaluate = $this->createTestingEelEvaluator();

        $return = Utility::evaluateEelExpression($expression, $compilingEvaluate, $contextVariables, $defaultContext);

        self::assertSame($expectedResult, $return);
    }

    /** @test */
    public function legacyMergeWithNeosFusionDefaultContext()
    {
        // simulate Neos.Fusion.defaultContext
        $eelBaseConfig = [
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
                ]
            ],
            "__internalLegacyConfig" => [
                'Example' => ExampleProtectedContextAwareHelper::class,
                'Foo.Bar.Example' => ExampleProtectedContextAwareHelper::class
            ]
        ];

        $myCustomLegacyConfig = [
            'MyExample' => ExampleProtectedContextAwareHelper::class,
            'Foo.My.Example' => ExampleProtectedContextAwareHelper::class
        ];

        $legacyConfigMerger = array_merge($eelBaseConfig, $myCustomLegacyConfig);

        $defaultContext = Utility::getDefaultContextVariables($legacyConfigMerger);

        self::assertEquals(
            [
                'Example' => new ExampleProtectedContextAwareHelper(),
                'Foo' => [
                    'Bar' => [
                        'Example' => new ExampleProtectedContextAwareHelper()
                    ],
                    'My' => [
                        'Example' => new ExampleProtectedContextAwareHelper()
                    ]
                ],
                'MyExample' => new ExampleProtectedContextAwareHelper(),
            ],
            $defaultContext
        );
    }

    /** @test */
    public function legacyIgnoredNewConfig()
    {
        $eelBaseConfig = [
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
                ]
            ]
        ];

        $defaultContext = Utility::getDefaultContextVariables($eelBaseConfig);

        self::assertEquals(
            [],
            $defaultContext
        );
    }
}
