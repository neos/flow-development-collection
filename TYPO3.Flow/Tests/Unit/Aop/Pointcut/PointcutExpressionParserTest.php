<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Pointcut;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 *
 */
class PointcutExpressionParserTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
     */
    protected $mockReflectionService;

    /**
     * Setup
     *
     * @return void
     */
    public function setup()
    {
        $this->mockObjectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->mockReflectionService = $this->getMockBuilder('TYPO3\Flow\Reflection\ReflectionService')->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseThrowsExceptionIfPointcutExpressionIsNotAString()
    {
        $parser = new PointcutExpressionParser();
        $parser->parse(false, 'Unit Test');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
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
        $mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClassAnnotatedWith', 'parseDesignatorClass', 'parseDesignatorMethodAnnotatedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting', 'parseRuntimeEvaluations');
        $parser = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser')->setMethods($mockMethods)->disableOriginalConstructor()->getMock();

        $parser->expects($this->once())->method('parseDesignatorPointcut')->with('&&', '\Foo\Bar->baz');
        $parser->expects($this->once())->method('parseDesignatorClassAnnotatedWith')->with('&&', 'TYPO3\Flow\Annotations\Aspect');
        $parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo');
        $parser->expects($this->once())->method('parseDesignatorMethodAnnotatedWith')->with('&&', 'TYPO3\Flow\Annotations\Session');
        $parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar()');
        $parser->expects($this->once())->method('parseDesignatorWithin')->with('&&', 'Bar');
        $parser->expects($this->once())->method('parseDesignatorFilter')->with('&&', '\Foo\Bar\Baz');
        $parser->expects($this->once())->method('parseDesignatorSetting')->with('&&', 'Foo.Bar.baz');
        $parser->expects($this->once())->method('parseRuntimeEvaluations')->with('&&', 'Foo.Bar.baz == "test"');

        $parser->parse('\Foo\Bar->baz', 'Unit Test');
        $parser->parse('classAnnotatedWith(TYPO3\Flow\Annotations\Aspect)', 'Unit Test');
        $parser->parse('class(Foo)', 'Unit Test');
        $parser->parse('methodAnnotatedWith(TYPO3\Flow\Annotations\Session)', 'Unit Test');
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
        $mockMethods = array('parseDesignatorMethod');
        $parser = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser')->setMethods($mockMethods)->disableOriginalConstructor()->getMock();
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar(firstArgument = "baz", secondArgument = TRUE)');

        $parser->parse('method(Foo->Bar(firstArgument = "baz", secondArgument = TRUE))', 'Unit Test');
    }

    /**
     * @test
     */
    public function parseSplitsUpTheExpressionIntoDesignatorsAndPassesTheOperatorsToTheDesginatorParseMethod()
    {
        $mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClass', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting');
        $parser = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser')->setMethods($mockMethods)->disableOriginalConstructor()->getMock();
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
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock('TYPO3\Flow\Log\SystemLoggerInterface')));

        $mockPointcutFilterComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorClassAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorClassAddsAFilterToTheGivenFilterComposite()
    {
        $mockPointcutFilterComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->injectReflectionService($this->mockReflectionService);

        $parser->_call('parseDesignatorClass', '&&', 'Foo', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorMethodAnnotatedWithAddsAFilterToTheGivenFilterComposite()
    {
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock('TYPO3\Flow\Log\SystemLoggerInterface')));

        $mockPointcutFilterComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethodAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseDesignatorMethodThrowsAnExceptionIfTheExpressionLacksTheClassMethodArrow()
    {
        $mockComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();
        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->_call('parseDesignatorMethod', '&&', 'Foo bar', $mockComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorMethodParsesVisibilityForPointcutMethodNameFilter()
    {
        $composite = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite', array('dummy'));

        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->createMock('TYPO3\Flow\Log\SystemLoggerInterface')));

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethod', '&&', 'protected Foo->bar()', $composite);
        $filters = $composite->_get('filters');
        foreach ($filters as $operatorAndFilter) {
            list(, $filter) = $operatorAndFilter;
            if ($filter instanceof \TYPO3\Flow\Aop\Pointcut\PointcutMethodNameFilter) {
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

        $expectedConditions = array(
            'arg1' => array(
                'operator' => array('=='),
                'value' => array('"blub,ber"')
            ),
            'arg2' => array(
                'operator' => array('!=', '=='),
                'value' => array('FALSE', 'TRUE')
            ),
            'arg3' => array(
                'operator' => array('in'),
                'value' => array(
                    array(
                        'TRUE',
                        'some.object.access',
                        '"fa,sel"',
                        '\'blub\''
                    )
                )
            ),
            'arg4' => array(
                'operator' => array('contains'),
                'value' => array('FALSE')
            ),
            'arg5' => array(
                'operator' => array('matches'),
                'value' => array(
                    array(1, 2, 3)
                )
            ),
            'arg6' => array(
                'operator' => array('matches'),
                'value' => array('current.party.accounts')
            )
        );

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);

        $result = $parser->_call('getArgumentConstraintsFromMethodArgumentsPattern', $methodArgumentsPattern);
        $this->assertEquals($expectedConditions, $result, 'The argument condition string has not been parsed as expected.');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseDesignatorPointcutThrowsAnExceptionIfTheExpressionLacksTheAspectClassMethodArrow()
    {
        $mockComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();
        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar', $mockComposite);
    }

    /**
     * @test
     */
    public function parseDesignatorFilterAddsACustomFilterToTheGivenFilterComposite()
    {
        $mockFilter = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilter')->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

        $this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorFilter', '&&', 'TYPO3\Foo\Custom\Filter', $mockPointcutFilterComposite);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseDesignatorFilterThrowsAnExceptionIfACustomFilterDoesNotImplementThePointcutFilterInterface()
    {
        $mockFilter = new \ArrayObject();
        $mockPointcutFilterComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();

        $this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', false);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorFilter', '&&', 'TYPO3\Foo\Custom\Filter', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
    public function parseRuntimeEvaluationsBasicallyWorks()
    {
        $expectedRuntimeEvaluationsDefinition = array(
            '&&' => array(
                'evaluateConditions' => array(
                    'parsed constraints'
                )
            )
        );

        $mockPointcutFilterComposite = $this->getMockBuilder('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite')->disableOriginalConstructor()->getMock();
        $mockPointcutFilterComposite->expects($this->once())->method('setGlobalRuntimeEvaluationsDefinition')->with($expectedRuntimeEvaluationsDefinition);

        $parser = $this->getAccessibleMock('TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser', array('getRuntimeEvaluationConditionsFromEvaluateString'), array(), '', false);
        $parser->expects($this->once())->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('some == constraint')->will($this->returnValue(array('parsed constraints')));

        $parser->_call('parseRuntimeEvaluations', '&&', 'some == constraint', $mockPointcutFilterComposite);
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationConditionsFromEvaluateStringReturnsTheCorrectArrayForAnEvaluateString()
    {
        $expectedRuntimeEvaluationsDefinition = array(
            array(
                'operator' => '==',
                'leftValue' => '"blub"',
                'rightValue' => '5',
            ),
            array(
                'operator' => '<=',
                'leftValue' => 'current.party.name',
                'rightValue' => '\'foo\'',
            ),
            array(
                'operator' => '!=',
                'leftValue' => 'this.attendee.name',
                'rightValue' => 'current.party.person.name',
            ),
            array(
                'operator' => 'in',
                'leftValue' => 'this.some.object',
                'rightValue' => array('TRUE', 'some.object.access')
            ),
            array(
                'operator' => 'matches',
                'leftValue' => 'this.some.object',
                'rightValue' => array(1,2,3)
            ),
            array(
                'operator' => 'matches',
                'leftValue' => 'this.some.arrayProperty',
                'rightValue' => 'current.party.accounts'
            )
        );

        $evaluateString = '"blub" == 5, current.party.name <= \'foo\', this.attendee.name != current.party.person.name, this.some.object in (TRUE, some.object.access), this.some.object matches (1, 2, 3), this.some.arrayProperty matches current.party.accounts';

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, array('dummy'), array(), '', false);
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

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, array('dummy'), array(), '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorClassAnnotatedWith', '&&', 'foo(bar == FALSE)', $pointcutFilterComposite);

        $expectedAnnotation = 'foo';
        $expectedAnnotationValueConstraints = array(
            'bar' => array(
                'operator' => array(
                    0 => '=='
                ),
                'value' => array(
                    0 => 'FALSE'
                )
            )
        );

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

        $parser = $this->getAccessibleMock(PointcutExpressionParser::class, array('dummy'), array(), '', false);
        $parser->injectReflectionService($this->mockReflectionService);
        $parser->injectObjectManager($this->mockObjectManager);

        $parser->_call('parseDesignatorMethodAnnotatedWith', '&&', 'foo(bar == FALSE)', $pointcutFilterComposite);

        $expectedAnnotation = 'foo';
        $expectedAnnotationValueConstraints = array(
            'bar' => array(
                'operator' => array(
                    0 => '=='
                ),
                'value' => array(
                    0 => 'FALSE'
                )
            )
        );

        $filters = ObjectAccess::getProperty($pointcutFilterComposite, 'filters', true);
        $filter = $filters[0][1];
        $annotation = ObjectAccess::getProperty($filter, 'annotation', true);
        $annotationValueConstraints = ObjectAccess::getProperty($filter, 'annotationValueConstraints', true);
        $this->assertEquals($expectedAnnotation, $annotation);
        $this->assertEquals($expectedAnnotationValueConstraints, $annotationValueConstraints);
    }
}
