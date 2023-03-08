<?php

namespace Neos\Eel\Tests\Functional\Utility;

use Neos\Cache\Frontend\StringFrontend;
use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Utility;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Annotations as Flow;

class UtilityTest extends FunctionalTestCase
{
    public function eelProvider(): iterable
    {
        yield "simple eel expression" => [
            "expression" => '${String.toString(variableName+2)}',
            "contextVariables" => ['variableName' => 2],
            "expectedResult" => '4'
        ];

        yield "top level function" => [
            "expression" => '${q(["value1", "value2"]).count()}',
            "contextVariables" => [],
            "expectedResult" => 2
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
}