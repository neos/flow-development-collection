<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Exception\InvalidPointcutExpressionException;
use Neos\Flow\Aop\Pointcut\PointcutExpressionParser;
use Neos\Flow\Aop\Pointcut\PointcutFilter;
use Neos\Flow\Aop\Pointcut\PointcutFilterComposite;
use Neos\Flow\Aop\Pointcut\PointcutMethodNameFilter;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 */
final class PointcutExpressionParserTest extends UnitTestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
    }

    #[Test]
    public function parseThrowsExceptionIfThePointcutExpressionContainsNoDesignator()
    {
        $this->expectException(InvalidPointcutExpressionException::class);
        $parser = new PointcutExpressionParser();
        $parser->injectObjectManager($this->mockObjectManager);
        $parser->parse('()', 'Unit Test');
    }

    #[Test]
    public function parseCallsSpecializedMethodsToParseEachDesignator()
    {
        $mockMethods = ['parseDesignatorPointcut', 'parseDesignatorClassAnnotatedWith', 'parseDesignatorClass', 'parseDesignatorMethodAnnotatedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting', 'parseRuntimeEvaluations'];
        $parser = $this->getMockBuilder(PointcutExpressionParser::class)->onlyMethods($mockMethods)->disableOriginalConstructor()->getMock();

        $parser->expects($this->once())->method('parseDesignatorPointcut')->with('&&', '\Foo\Bar->baz');
        $parser->expects($this->once())->method('parseDesignatorClassAnnotatedWith')->with('&&', Flow\Aspect::class);
        $parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo');
        $parser->expects($this->once())->method('parseDesignatorMethodAnnotatedWith')->with('&&', Flow\Session::class);
        $parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar()');
        $parser->expects($this->once())->method('parseDesignatorWithin')->with('&&', 'Bar');
        $parser->expects($this->once())->method('parseDesignatorFilter')->with('&&', '\Foo\Bar\Baz');
        $parser->expects($this->once())->method('parseDesignatorSetting')->with('&&', 'Foo.Bar.baz');
        $parser->expects($this->once())->method('parseRuntimeEvaluations')->with('&&', 'Foo.Bar.baz == "test"');

        $parser->parse('\Foo\Bar->baz', 'Unit Test');
        $parser->parse('classAnnotatedWith(Neos\Flow\Annotations\Aspect)', 'Unit Test');
        $parser->parse('class(Foo)', 'Unit Test');
        $parser->parse('methodAnnotatedWith(Neos\Flow\Annotations\Session)', 'Unit Test');
        $parser->parse('method(Foo->Bar())', 'Unit Test');
        $parser->parse('within(Bar)', 'Unit Test');
        $parser->parse('filter(\Foo\Bar\Baz)', 'Unit Test');
        $parser->parse('setting(Foo.Bar.baz)', 'Unit Test');
        $parser->parse('evaluate(Foo.Bar.baz == "test")', 'Unit Test');
    }

    #[Test]
    public function parseCallsParseDesignatorMethodWithTheCorrectSignaturePatternStringIfTheExpressionContainsArgumentPatterns()
    {
        $mockMethods = ['parseDesignatorMethod'];
        $parser = $this->getMockBuilder(PointcutExpressionParser::class)->onlyMethods($mockMethods)->disableOriginalConstructor()->getMock();
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar(firstArgument = "baz", secondArgument = true)');

        $parser->parse('method(Foo->Bar(firstArgument = "baz", secondArgument = true))', 'Unit Test');
    }

    #[Test]
    public function parseSplitsUpTheExpressionIntoDesignatorsAndPassesTheOperatorsToTheDesginatorParseMethod()
    {
        $mockMethods = ['parseDesignatorPointcut', 'parseDesignatorClass', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting'];
        $parser = $this->getMockBuilder(PointcutExpressionParser::class)->onlyMethods($mockMethods)->disableOriginalConstructor()->getMock();
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo');
        $parser->expects($this->once())->method('parseDesignatorMethod')->with('||', 'Foo->Bar()');
        $parser->expects($this->once())->method('parseDesignatorWithin')->with('&&!', 'Bar');

        $parser->parse('class(Foo) || method(Foo->Bar()) && !within(Bar)', 'Unit Test');
    }

    #[Test]
    public function parseDesignatorClassAnnotatedWithAddsAFilterToTheGivenFilterComposite()
    {
        $mockPsrLoggerFactory = $this->createMock(PsrLoggerFactoryInterface::class);
        $mockPsrLoggerFactory->method('get')->willReturn($this->createMock(LoggerInterface::class));

        $this->mockObjectManager->method('get')->willReturn($mockPsrLoggerFactory);

        $mockPointcutFilterComposite = $this->createMock(PointcutFilterComposite::class);
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectReflectionService($this->createStub(ReflectionService::class));
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorClassAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
    }

    #[Test]
    public function parseDesignatorClassAddsAFilterToTheGivenFilterComposite()
    {
        $mockPointcutFilterComposite = $this->createMock(PointcutFilterComposite::class);
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectReflectionService($this->createStub(ReflectionService::class));

        $parser->_call('parseDesignatorClass', '&&', 'Foo', $mockPointcutFilterComposite);
    }

    #[Test]
    public function parseDesignatorMethodAnnotatedWithAddsAFilterToTheGivenFilterComposite()
    {
        $mockPsrLoggerFactory = $this->createMock(PsrLoggerFactoryInterface::class);
        $mockPsrLoggerFactory->method('get')->willReturn($this->createMock(LoggerInterface::class));

        $this->mockObjectManager->method('get')->willReturn($mockPsrLoggerFactory);

        $mockPointcutFilterComposite = $this->createMock(PointcutFilterComposite::class);
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectReflectionService($this->createStub(ReflectionService::class));
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethodAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
    }

    #[Test]
    public function parseDesignatorMethodThrowsAnExceptionIfTheExpressionLacksTheClassMethodArrow()
    {
        $this->expectException(InvalidPointcutExpressionException::class);
        $mockComposite = $this->createStub(PointcutFilterComposite::class);
        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->_call('parseDesignatorMethod', '&&', 'Foo bar', $mockComposite);
    }

    #[Test]
    public function parseDesignatorMethodParsesVisibilityForPointcutMethodNameFilter()
    {
        $composite = $this->getAccessibleMock(PointcutFilterComposite::class, []);

        $mockPsrLoggerFactory = $this->createMock(PsrLoggerFactoryInterface::class);
        $mockPsrLoggerFactory->method('get')->willReturn($this->createMock(LoggerInterface::class));

        $this->mockObjectManager->method('get')->willReturn($mockPsrLoggerFactory);

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectReflectionService($this->createStub(ReflectionService::class));
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethod', '&&', 'protected Foo->bar()', $composite);
        $filters = $composite->_get('filters');
        foreach ($filters as $operatorAndFilter) {
            list(, $filter) = $operatorAndFilter;
            if ($filter instanceof PointcutMethodNameFilter) {
                self::assertSame('protected', $filter->getMethodVisibility());
                return;
            }
        }
        $this->fail('No filter for method name found');
    }

    #[Test]
    public function getArgumentConstraintsFromMethodArgumentsPatternWorks()
    {
        $methodArgumentsPattern = 'arg1 == "blub,ber",   arg2 != false  ,arg3 in   (true, some.object.access, "fa,sel", \'blub\'), arg4 contains false,arg2==true,arg5 matches (1,2,3), arg6 matches current.party.accounts';

        $expectedConditions = [
            'arg1' => [
                'operator' => ['=='],
                'value' => ['"blub,ber"']
            ],
            'arg2' => [
                'operator' => ['!=', '=='],
                'value' => ['false', 'true']
            ],
            'arg3' => [
                'operator' => ['in'],
                'value' => [
                    [
                        'true',
                        'some.object.access',
                        '"fa,sel"',
                        '\'blub\''
                    ]
                ]
            ],
            'arg4' => [
                'operator' => ['contains'],
                'value' => ['false']
            ],
            'arg5' => [
                'operator' => ['matches'],
                'value' => [
                    [1, 2, 3]
                ]
            ],
            'arg6' => [
                'operator' => ['matches'],
                'value' => ['current.party.accounts']
            ]
        ];

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);

        $result = $parser->_call('getArgumentConstraintsFromMethodArgumentsPattern', $methodArgumentsPattern);
        self::assertEquals($expectedConditions, $result, 'The argument condition string has not been parsed as expected.');
    }

    #[Test]
    public function parseDesignatorPointcutThrowsAnExceptionIfTheExpressionLacksTheAspectClassMethodArrow()
    {
        $this->expectException(InvalidPointcutExpressionException::class);
        $mockComposite = $this->createStub(PointcutFilterComposite::class);
        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar', $mockComposite);
    }

    #[Test]
    public function parseDesignatorFilterAddsACustomFilterToTheGivenFilterComposite()
    {
        $mockFilter = $this->createStub(PointcutFilter::class);
        $mockPointcutFilterComposite = $this->createMock(PointcutFilterComposite::class);
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

        $this->mockObjectManager->expects($this->once())->method('get')->with('Neos\Foo\Custom\Filter')->willReturn(($mockFilter));

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorFilter', '&&', 'Neos\Foo\Custom\Filter', $mockPointcutFilterComposite);
    }

    #[Test]
    public function parseDesignatorFilterThrowsAnExceptionIfACustomFilterDoesNotImplementThePointcutFilterInterface()
    {
        $this->expectException(InvalidPointcutExpressionException::class);
        $mockFilter = new \ArrayObject();
        $mockPointcutFilterComposite = $this->createStub(PointcutFilterComposite::class);

        $this->mockObjectManager->expects($this->once())->method('get')->with('Neos\Foo\Custom\Filter')->willReturn(($mockFilter));

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorFilter', '&&', 'Neos\Foo\Custom\Filter', $mockPointcutFilterComposite);
    }

    #[Test]
    public function parseRuntimeEvaluationsBasicallyWorks()
    {
        $expectedRuntimeEvaluationsDefinition = [
            '&&' => [
                'evaluateConditions' => [
                    'parsed constraints'
                ]
            ]
        ];

        $mockPointcutFilterComposite = $this->createMock(PointcutFilterComposite::class);
        $mockPointcutFilterComposite->expects($this->once())->method('setGlobalRuntimeEvaluationsDefinition')->with($expectedRuntimeEvaluationsDefinition);

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['getRuntimeEvaluationConditionsFromEvaluateString'], [], '', false);
        $parser->expects($this->once())->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('some == constraint')->willReturn((['parsed constraints']));

        $parser->_call('parseRuntimeEvaluations', '&&', 'some == constraint', $mockPointcutFilterComposite);
    }

    #[Test]
    public function getRuntimeEvaluationConditionsFromEvaluateStringReturnsTheCorrectArrayForAnEvaluateString()
    {
        $expectedRuntimeEvaluationsDefinition = [
            [
                'operator' => '==',
                'leftValue' => '"blub"',
                'rightValue' => '5',
            ],
            [
                'operator' => '<=',
                'leftValue' => 'current.party.name',
                'rightValue' => '\'foo\'',
            ],
            [
                'operator' => '!=',
                'leftValue' => 'this.attendee.name',
                'rightValue' => 'current.party.person.name',
            ],
            [
                'operator' => 'in',
                'leftValue' => 'this.some.object',
                'rightValue' => ['true', 'some.object.access']
            ],
            [
                'operator' => 'matches',
                'leftValue' => 'this.some.object',
                'rightValue' => [1,2,3]
            ],
            [
                'operator' => 'matches',
                'leftValue' => 'this.some.arrayProperty',
                'rightValue' => 'current.party.accounts'
            ]
        ];

        $evaluateString = '"blub" == 5, current.party.name <= \'foo\', this.attendee.name != current.party.person.name, this.some.object in (true, some.object.access), this.some.object matches (1, 2, 3), this.some.arrayProperty matches current.party.accounts';

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $result = $parser->_call('getRuntimeEvaluationConditionsFromEvaluateString', $evaluateString);

        self::assertEquals($result, $expectedRuntimeEvaluationsDefinition, 'The string has not been parsed correctly.');
    }

    #[Test]
    public function parseDesignatorClassAnnotatedWithObservesAnnotationPropertyConstraints()
    {
        $mockPsrLoggerFactory = $this->createMock(PsrLoggerFactoryInterface::class);
        $mockPsrLoggerFactory->method('get')->willReturn($this->createMock(LoggerInterface::class));

        $this->mockObjectManager->method('get')->willReturn($mockPsrLoggerFactory);

        $pointcutFilterComposite = new PointcutFilterComposite();

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectReflectionService($this->createStub(ReflectionService::class));
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorClassAnnotatedWith', '&&', 'foo(bar == false)', $pointcutFilterComposite);

        $expectedAnnotation = 'foo';
        $expectedAnnotationValueConstraints = [
            'bar' => [
                'operator' => [
                    0 => '=='
                ],
                'value' => [
                    0 => 'false'
                ]
            ]
        ];

        $filters = ObjectAccess::getProperty($pointcutFilterComposite, 'filters', true);
        $filter = $filters[0][1];
        $annotation = ObjectAccess::getProperty($filter, 'annotation', true);
        $annotationValueConstraints = ObjectAccess::getProperty($filter, 'annotationValueConstraints', true);
        self::assertEquals($expectedAnnotation, $annotation);
        self::assertEquals($expectedAnnotationValueConstraints, $annotationValueConstraints);
    }

    #[Test]
    public function parseDesignatorMethodAnnotatedWithObservesAnnotationPropertyConstraints()
    {
        $mockPsrLoggerFactory = $this->createMock(PsrLoggerFactoryInterface::class);
        $mockPsrLoggerFactory->method('get')->willReturn($this->createMock(LoggerInterface::class));

        $this->mockObjectManager->method('get')->willReturn($mockPsrLoggerFactory);

        $pointcutFilterComposite = new PointcutFilterComposite();

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, [], [], '', false);
        $parser->injectReflectionService($this->createStub(ReflectionService::class));
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethodAnnotatedWith', '&&', 'foo(bar == false)', $pointcutFilterComposite);

        $expectedAnnotation = 'foo';
        $expectedAnnotationValueConstraints = [
            'bar' => [
                'operator' => [
                    0 => '=='
                ],
                'value' => [
                    0 => 'false'
                ]
            ]
        ];

        $filters = ObjectAccess::getProperty($pointcutFilterComposite, 'filters', true);
        $filter = $filters[0][1];
        $annotation = ObjectAccess::getProperty($filter, 'annotation', true);
        $annotationValueConstraints = ObjectAccess::getProperty($filter, 'annotationValueConstraints', true);
        self::assertEquals($expectedAnnotation, $annotation);
        self::assertEquals($expectedAnnotationValueConstraints, $annotationValueConstraints);
    }
}
