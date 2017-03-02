<?php
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

use Neos\Flow\Aop\Pointcut\PointcutExpressionParser;
use Neos\Flow\Aop\Pointcut\PointcutFilterComposite;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Aop;
use Neos\Flow\Annotations as Flow;

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 */
class PointcutExpressionParserTest extends UnitTestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    /**
     * Setup
     *
     * @return void
     */
    public function setup()
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseThrowsExceptionIfPointcutExpressionIsNotAString()
    {
        $parser = new PointcutExpressionParser();
        $parser->parse(false, 'Unit Test');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseThrowsExceptionIfThePointcutExpressionContainsNoDesignator()
    {
        $parser = new PointcutExpressionParser();
        $parser->injectObjectManager($this->mockObjectManager);
        $parser->parse('()', 'Unit Test');
    }

    /**
     * @test
     */
    public function parseCallsSpecializedMethodsToParseEachDesignator()
    {
        $mockMethods = ['parseDesignatorPointcut', 'parseDesignatorClassAnnotatedWith', 'parseDesignatorClass', 'parseDesignatorMethodAnnotatedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting', 'parseRuntimeEvaluations'];
        $parser = $this->getMockBuilder(PointcutExpressionParser::class)->setMethods($mockMethods)->disableOriginalConstructor()->getMock();

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

    /**
     * @test
     */
    public function parseCallsParseDesignatorMethodWithTheCorrectSignaturePatternStringIfTheExpressionContainsArgumentPatterns()
    {
        $mockMethods = ['parseDesignatorMethod'];
        $parser = $this->getMockBuilder(PointcutExpressionParser::class)->setMethods($mockMethods)->disableOriginalConstructor()->getMock();
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar(firstArgument = "baz", secondArgument = TRUE)');

        $parser->parse('method(Foo->Bar(firstArgument = "baz", secondArgument = TRUE))', 'Unit Test');
    }

    /**
     * @test
     */
    public function parseSplitsUpTheExpressionIntoDesignatorsAndPassesTheOperatorsToTheDesginatorParseMethod()
    {
        $mockMethods = ['parseDesignatorPointcut', 'parseDesignatorClass', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting'];
        $parser = $this->getMockBuilder(PointcutExpressionParser::class)->setMethods($mockMethods)->disableOriginalConstructor()->getMock();
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo');
        $parser->expects($this->once())->method('parseDesignatorMethod')->with('||', 'Foo->Bar()');
        $parser->expects($this->once())->method('parseDesignatorWithin')->with('&&!', 'Bar');

        $parser->parse('class(Foo) || method(Foo->Bar()) && !within(Bar)', 'Unit Test');
    }

    /**
     * @test
     */
    public function parseDesignatorClassAnnotatedWithAddsAFilterToTheGivenFilterComposite()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock(SystemLoggerInterface::class)));

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorClassAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorClassAddsAFilterToTheGivenFilterComposite()
    {
        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectReflectionService($this->mockReflectionService);

        $parser->_call('parseDesignatorClass', '&&', 'Foo', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorMethodAnnotatedWithAddsAFilterToTheGivenFilterComposite()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock(SystemLoggerInterface::class)));

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethodAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseDesignatorMethodThrowsAnExceptionIfTheExpressionLacksTheClassMethodArrow()
    {
        $mockComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->_call('parseDesignatorMethod', '&&', 'Foo bar', $mockComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorMethodParsesVisibilityForPointcutMethodNameFilter()
    {
        $composite = $this->getAccessibleMock(PointcutFilterComposite::class, ['dummy']);

        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock(SystemLoggerInterface::class)));

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethod', '&&', 'protected Foo->bar()', $composite);
        $filters = $composite->_get('filters');
        foreach ($filters as $operatorAndFilter) {
            list(, $filter) = $operatorAndFilter;
            if ($filter instanceof Aop\Pointcut\PointcutMethodNameFilter) {
                $this->assertEquals('protected', $filter->getMethodVisibility());
                return;
            }
        }
        $this->fail('No filter for method name found');
    }

    /**
     * @test
     */
    public function getArgumentConstraintsFromMethodArgumentsPatternWorks()
    {
        $methodArgumentsPattern = 'arg1 == "blub,ber",   arg2 != FALSE  ,arg3 in   (TRUE, some.object.access, "fa,sel", \'blub\'), arg4 contains FALSE,arg2==TRUE,arg5 matches (1,2,3), arg6 matches current.party.accounts';

        $expectedConditions = [
            'arg1' => [
                'operator' => ['=='],
                'value' => ['"blub,ber"']
            ],
            'arg2' => [
                'operator' => ['!=', '=='],
                'value' => ['FALSE', 'TRUE']
            ],
            'arg3' => [
                'operator' => ['in'],
                'value' => [
                    [
                        'TRUE',
                        'some.object.access',
                        '"fa,sel"',
                        '\'blub\''
                    ]
                ]
            ],
            'arg4' => [
                'operator' => ['contains'],
                'value' => ['FALSE']
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

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);

        $result = $parser->_call('getArgumentConstraintsFromMethodArgumentsPattern', $methodArgumentsPattern);
        $this->assertEquals($expectedConditions, $result, 'The argument condition string has not been parsed as expected.');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseDesignatorPointcutThrowsAnExceptionIfTheExpressionLacksTheAspectClassMethodArrow()
    {
        $mockComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar', $mockComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorFilterAddsACustomFilterToTheGivenFilterComposite()
    {
        $mockFilter = $this->getMockBuilder(Aop\Pointcut\PointcutFilter::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

        $this->mockObjectManager->expects($this->once())->method('get')->with('Neos\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorFilter', '&&', 'Neos\Foo\Custom\Filter', $mockPointcutFilterComposite);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseDesignatorFilterThrowsAnExceptionIfACustomFilterDoesNotImplementThePointcutFilterInterface()
    {
        $mockFilter = new \ArrayObject();
        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();

        $this->mockObjectManager->expects($this->once())->method('get')->with('Neos\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorFilter', '&&', 'Neos\Foo\Custom\Filter', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
    public function parseRuntimeEvaluationsBasicallyWorks()
    {
        $expectedRuntimeEvaluationsDefinition = [
            '&&' => [
                'evaluateConditions' => [
                    'parsed constraints'
                ]
            ]
        ];

        $mockPointcutFilterComposite = $this->getMockBuilder(PointcutFilterComposite::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('setGlobalRuntimeEvaluationsDefinition')->with($expectedRuntimeEvaluationsDefinition);

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['getRuntimeEvaluationConditionsFromEvaluateString'], [], '', false);
        $parser->expects($this->once())->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('some == constraint')->will($this->returnValue(['parsed constraints']));

        $parser->_call('parseRuntimeEvaluations', '&&', 'some == constraint', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
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
                'rightValue' => ['TRUE', 'some.object.access']
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

        $evaluateString = '"blub" == 5, current.party.name <= \'foo\', this.attendee.name != current.party.person.name, this.some.object in (TRUE, some.object.access), this.some.object matches (1, 2, 3), this.some.arrayProperty matches current.party.accounts';

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $result = $parser->_call('getRuntimeEvaluationConditionsFromEvaluateString', $evaluateString);

        $this->assertEquals($result, $expectedRuntimeEvaluationsDefinition, 'The string has not been parsed correctly.');
    }

    /**
     * @test
     */
    public function parseDesignatorClassAnnotatedWithObservesAnnotationPropertyConstraints()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock(SystemLoggerInterface::class)));

        $pointcutFilterComposite = new PointcutFilterComposite();

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorClassAnnotatedWith', '&&', 'foo(bar == FALSE)', $pointcutFilterComposite);

        $expectedAnnotation = 'foo';
        $expectedAnnotationValueConstraints = [
            'bar' => [
                'operator' => [
                    0 => '=='
                ],
                'value' => [
                    0 => 'FALSE'
                ]
            ]
        ];

        $filters = ObjectAccess::getProperty($pointcutFilterComposite, 'filters', true);
        $filter = $filters[0][1];
        $annotation = ObjectAccess::getProperty($filter, 'annotation', true);
        $annotationValueConstraints = ObjectAccess::getProperty($filter, 'annotationValueConstraints', true);
        $this->assertEquals($expectedAnnotation, $annotation);
        $this->assertEquals($expectedAnnotationValueConstraints, $annotationValueConstraints);
    }

    /**
     * @test
     */
    public function parseDesignatorMethodAnnotatedWithObservesAnnotationPropertyConstraints()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock(SystemLoggerInterface::class)));

        $pointcutFilterComposite = new PointcutFilterComposite();

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, ['dummy'], [], '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethodAnnotatedWith', '&&', 'foo(bar == FALSE)', $pointcutFilterComposite);

        $expectedAnnotation = 'foo';
        $expectedAnnotationValueConstraints = [
            'bar' => [
                'operator' => [
                    0 => '=='
                ],
                'value' => [
                    0 => 'FALSE'
                ]
            ]
        ];

        $filters = ObjectAccess::getProperty($pointcutFilterComposite, 'filters', true);
        $filter = $filters[0][1];
        $annotation = ObjectAccess::getProperty($filter, 'annotation', true);
        $annotationValueConstraints = ObjectAccess::getProperty($filter, 'annotationValueConstraints', true);
        $this->assertEquals($expectedAnnotation, $annotation);
        $this->assertEquals($expectedAnnotationValueConstraints, $annotationValueConstraints);
    }
}
