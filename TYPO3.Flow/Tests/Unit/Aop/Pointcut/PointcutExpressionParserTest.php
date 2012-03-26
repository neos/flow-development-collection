<?php
namespace TYPO3\FLOW3\Tests\Unit\Aop\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 *
 */
class PointcutExpressionParserTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInteface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setup() {
		$this->mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function parseThrowsExceptionIfPointcutExpressionIsNotAString() {
		$parser = new \TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser();
		$parser->parse(FALSE, 'Unit Test');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function parseThrowsExceptionIfThePointcutExpressionContainsNoDesignator() {
		$parser = new \TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser();
		$parser->injectObjectManager($this->mockObjectManager);
		$parser->parse('()', 'Unit Test');
	}

	/**
	 * @test
	 */
	public function parseCallsSpecializedMethodsToParseEachDesignator() {
		$mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClassAnnotatedWith', 'parseDesignatorClass', 'parseDesignatorMethodAnnotatedWith', 'parseDesignatorMethodTaggedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting', 'parseRuntimeEvaluations');
		$parser = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', $mockMethods, array(), '', FALSE);

		$parser->expects($this->once())->method('parseDesignatorPointcut')->with('&&', '\Foo\Bar->baz');
		$parser->expects($this->once())->method('parseDesignatorClassAnnotatedWith')->with('&&', 'TYPO3\FLOW3\Annotations\Aspect');
		$parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo');
		$parser->expects($this->once())->method('parseDesignatorMethodAnnotatedWith')->with('&&', 'TYPO3\FLOW3\Annotations\Session');
		$parser->expects($this->once())->method('parseDesignatorMethodTaggedWith')->with('&&', 'foo');
		$parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar()');
		$parser->expects($this->once())->method('parseDesignatorWithin')->with('&&', 'Bar');
		$parser->expects($this->once())->method('parseDesignatorFilter')->with('&&', '\Foo\Bar\Baz');
		$parser->expects($this->once())->method('parseDesignatorSetting')->with('&&', 'Foo.Bar.baz');
		$parser->expects($this->once())->method('parseRuntimeEvaluations')->with('&&', 'Foo.Bar.baz == "test"');

		$parser->parse('\Foo\Bar->baz', 'Unit Test');
		$parser->parse('classAnnotatedWith(TYPO3\FLOW3\Annotations\Aspect)', 'Unit Test');
		$parser->parse('class(Foo)', 'Unit Test');
		$parser->parse('methodAnnotatedWith(TYPO3\FLOW3\Annotations\Session)', 'Unit Test');
		$parser->parse('methodTaggedWith(foo)', 'Unit Test');
		$parser->parse('method(Foo->Bar())', 'Unit Test');
		$parser->parse('within(Bar)', 'Unit Test');
		$parser->parse('filter(\Foo\Bar\Baz)', 'Unit Test');
		$parser->parse('setting(Foo.Bar.baz)', 'Unit Test');
		$parser->parse('evaluate(Foo.Bar.baz == "test")', 'Unit Test');
	}

        /**
	 * @test
	 */
	public function parseCallsParseDesignatorMethodWithTheCorrectSignaturePatternStringIfTheExpressionContainsArgumentPatterns() {
		$mockMethods = array('parseDesignatorMethod');
		$parser = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', $mockMethods, array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar(firstArgument = "baz", secondArgument = TRUE)');

		$parser->parse('method(Foo->Bar(firstArgument = "baz", secondArgument = TRUE))', 'Unit Test');
	}

	/**
	 * @test
	 */
	public function parseSplitsUpTheExpressionIntoDesignatorsAndPassesTheOperatorsToTheDesginatorParseMethod() {
		$mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClass', 'parseDesignatorMethodTaggedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting');
		$parser = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', $mockMethods, array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo');
		$parser->expects($this->once())->method('parseDesignatorMethod')->with('||', 'Foo->Bar()');
		$parser->expects($this->once())->method('parseDesignatorWithin')->with('&&!', 'Bar');

		$parser->parse('class(Foo) || method(Foo->Bar()) && !within(Bar)', 'Unit Test');
	}

	/**
	 * @test
	 */
	public function parseDesignatorClassAnnotatedWithAddsAFilterToTheGivenFilterComposite() {
		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->injectReflectionService($this->mockReflectionService);

		$parser->_call('parseDesignatorClassAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 */
	public function parseDesignatorClassAddsAFilterToTheGivenFilterComposite() {
		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->injectReflectionService($this->mockReflectionService);

		$parser->_call('parseDesignatorClass', '&&', 'Foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 */
	public function parseDesignatorMethodAnnotatedWithAddsAFilterToTheGivenFilterComposite() {
		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->injectReflectionService($this->mockReflectionService);

		$parser->_call('parseDesignatorMethodAnnotatedWith', '&&', 'foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 */
	public function parseDesignatorMethodTaggedWithAddsAFilterToTheGivenFilterComposite() {
		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&');

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->injectReflectionService($this->mockReflectionService);

		$parser->_call('parseDesignatorMethodTaggedWith', '&&', 'foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function parseDesignatorMethodThrowsAnExceptionIfTheExpressionLacksTheClassMethodArrow() {
		$mockComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->_call('parseDesignatorMethod', '&&', 'Foo bar', $mockComposite);
	}

	/**
	 * @test
	 */
	public function parseDesignatorMethodParsesVisibilityForPointcutMethodNameFilter() {
		$composite = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array('dummy'));

		$mockLogger = $this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface');
		$this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockLogger));

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->injectReflectionService($this->mockReflectionService);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->_call('parseDesignatorMethod', '&&', 'protected Foo->bar()', $composite);
		$filters = $composite->_get('filters');
		foreach ($filters as $operatorAndFilter) {
			list($operator, $filter) = $operatorAndFilter;
			if ($filter instanceof \TYPO3\FLOW3\Aop\Pointcut\PointcutMethodNameFilter) {
				$this->assertEquals('protected', $filter->getMethodVisibility());
				return;
			}
		}
		$this->fail('No filter for method name found');
	}

	/**
	 * @test
	 */
	public function getArgumentConstraintsFromMethodArgumentsPatternWorks() {
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

		$parser = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);

		$result = $parser->_call('getArgumentConstraintsFromMethodArgumentsPattern', $methodArgumentsPattern);
		$this->assertEquals($expectedConditions, $result, 'The argument condition string has not been parsed as expected.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function parseDesignatorPointcutThrowsAnExceptionIfTheExpressionLacksTheAspectClassMethodArrow() {
		$mockComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar', $mockComposite);
	}

	/**
	 * @test
	 */
	public function parseDesignatorFilterAddsACustomFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->_call('parseDesignatorFilter', '&&', 'TYPO3\Foo\Custom\Filter', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function parseDesignatorFilterThrowsAnExceptionIfACustomFilterDoesNotImplementThePointcutFilterInterface() {
		$mockFilter = new \ArrayObject();
		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser', array('dummy'), array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->_call('parseDesignatorFilter', '&&', 'TYPO3\Foo\Custom\Filter', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 */
	public function parseRuntimeEvaluationsBasicallyWorks() {
		$expectedRuntimeEvaluationsDefinition = array(
			'&&' => array (
				'evaluateConditions' => array(
					'parsed constraints'
				)
			)
		);

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('setGlobalRuntimeEvaluationsDefinition')->with($expectedRuntimeEvaluationsDefinition);

		$parser = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser'), array('getRuntimeEvaluationConditionsFromEvaluateString'), array(), '', FALSE);
		$parser->expects($this->once())->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('some == constraint')->will($this->returnValue(array('parsed constraints')));

		$parser->_call('parseRuntimeEvaluations', '&&', 'some == constraint', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 */
	public function getRuntimeEvaluationConditionsFromEvaluateStringReturnsTheCorrectArrayForAnEvaluateString() {
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

		$parser = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$result = $parser->_call('getRuntimeEvaluationConditionsFromEvaluateString', $evaluateString);

		$this->assertEquals($result, $expectedRuntimeEvaluationsDefinition, 'The string has not been parsed correctly.');
	}
}
?>