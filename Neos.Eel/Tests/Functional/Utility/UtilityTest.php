<?php

namespace Neos\Eel\Tests\Functional\Utility;

use Neos\Cache\Frontend\StringFrontend;
use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Tests\Functional\Utility\Fixtures\ExampleProtectedContextAwareHelper;
use Neos\Eel\Tests\Functional\Utility\Fixtures\ExampleStaticFactoryFunction;
use Neos\Eel\Utility;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\FunctionalTestCase;

class UtilityTest extends FunctionalTestCase
{
    public function eelProvider(): iterable
    {
        yield 'simple eel expression' => [
            'expression' => '${String.toString(variableName+2)}',
            'contextVariables' => ['variableName' => 2],
            'expectedResult' => '4'
        ];

        yield 'top level function' => [
            'expression' => '${q(["value1", "value2"]).count()}',
            'contextVariables' => [],
            'expectedResult' => 2
        ];
    }

    /**
     * @test
     * @dataProvider eelProvider
     */
    public function evaluateEelExpressions(string $expression, array $contextVariables, mixed $expectedResult): void
    {
        $configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $defaultContext = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Eel.defaultContext');

        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $stringFrontendMock->method('has')->willReturn(false);

        $compilingEvaluate = new CompilingEvaluator();
        $compilingEvaluate->injectExpressionCache($stringFrontendMock);

        $return = Utility::evaluateEelExpression($expression, $compilingEvaluate, $contextVariables, $defaultContext);

        self::assertSame($expectedResult, $return);
    }

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
        $this->expectExceptionMessage('Function helpers are only allowed on root level, "Foo.example" was given?');
        Utility::getDefaultContextVariables($defaultContextConfiguration);
    }
}