<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PointcutExpressionParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\FactoryInteface
	 */
	protected $mockObjectFactory;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setup() {
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface', array(), array(), '', FALSE);
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseThrowsExceptionIfPointcutExpressionIsNotAString() {
		$parser = new \F3\FLOW3\AOP\Pointcut\PointcutExpressionParser();
		$parser->parse(FALSE);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseThrowsExceptionIfThePointcutExpressionContainsNoDesignator() {
		$parser = new \F3\FLOW3\AOP\Pointcut\PointcutExpressionParser();
		$parser->injectObjectFactory($this->mockObjectFactory);
		$parser->parse('()');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseCallsSpecializedMethodsToParseEachDesignator() {
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClassTaggedWith', 'parseDesignatorClass', 'parseDesignatorMethodTaggedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting', 'parseRuntimeEvaluations');
		$parser = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser', $mockMethods, array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->expects($this->once())->method('parseDesignatorPointcut')->with('&&', '\Foo\Bar->baz', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorClassTaggedWith')->with('&&', 'foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorMethodTaggedWith')->with('&&', 'foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar()', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorWithin')->with('&&', 'Bar', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorFilter')->with('&&', '\Foo\Bar\Baz', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorSetting')->with('&&', 'Foo.Bar.baz', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseRuntimeEvaluations')->with('&&', 'Foo.Bar.baz == "test"', $mockPointcutFilterComposite);

		$parser->parse('\Foo\Bar->baz');
		$parser->parse('classTaggedWith(foo)');
		$parser->parse('class(Foo)');
		$parser->parse('methodTaggedWith(foo)');
		$parser->parse('method(Foo->Bar())');
		$parser->parse('within(Bar)');
		$parser->parse('filter(\Foo\Bar\Baz)');
		$parser->parse('setting(Foo.Bar.baz)');
		$parser->parse('evaluate(Foo.Bar.baz == "test")');
	}

        /**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseCallsParseDesignatorMethodWithTheCorrectSignaturePatternStringIfTheExpressionContainsArgumentPatterns() {
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockMethods = array('parseDesignatorMethod');
		$parser = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser', $mockMethods, array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar(firstArgument = "baz", secondArgument = TRUE)', $mockPointcutFilterComposite);

		$parser->parse('method(Foo->Bar(firstArgument = "baz", secondArgument = TRUE))');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseSplitsUpTheExpressionIntoDesignatorsAndPassesTheOperatorsToTheDesginatorParseMethod() {
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClassTaggedWith', 'parseDesignatorClass', 'parseDesignatorMethodTaggedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting');
		$parser = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser', $mockMethods, array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->expects($this->once())->method('parseDesignatorClassTaggedWith')->with('&&', 'foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorMethod')->with('||', 'Foo->Bar()', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorWithin')->with('&&!', 'Bar', $mockPointcutFilterComposite);

		$parser->parse('classTaggedWith(foo) && class(Foo) || method(Foo->Bar()) && !within(Bar)');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorClassTaggedWithAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter', 'foo')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorClassTaggedWith', '&&', 'foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorClassAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter', 'Foo')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorClass', '&&', 'Foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorMethodTaggedWithAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutMethodTaggedWithFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutMethodTaggedWithFilter', 'foo')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorMethodTaggedWith', '&&', 'foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorMethodAddsAClassNameFilterAndAMethodNameFilterToTheGivenFilterComposite() {
		$mockClassNameFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter', array(), array(), '', FALSE);
		$mockMethodNameFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter', array(), array(), '', FALSE);

		$mockSubComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockSubComposite->expects($this->at(0))->method('addFilter')->with('&&', $mockClassNameFilter);
		$mockSubComposite->expects($this->at(1))->method('addFilter')->with('&&', $mockMethodNameFilter);

		$mockComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockComposite->expects($this->once())->method('addFilter')->with('&&', $mockSubComposite);

		$this->mockObjectFactory->expects($this->at(0))->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite')->will($this->returnValue($mockSubComposite));
		$this->mockObjectFactory->expects($this->at(1))->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter', 'Foo')->will($this->returnValue($mockClassNameFilter));
		$this->mockObjectFactory->expects($this->at(2))->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter', 'bar', 'protected')->will($this->returnValue($mockMethodNameFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorMethod', '&&', 'protected Foo->bar()', $mockComposite);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorMethodThrowsAnExceptionIfTheExpressionLacksTheClassMethodArrow() {
		$mockComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->_call('parseDesignatorMethod', '&&', 'Foo bar', $mockComposite);
	}

        /**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseDesignatorMethodSetsConditionsFromArgumentPatternsCorrectly() {
                $conditions = array(
                    'firstArgument' => array('operator' => '!=', 'value' => '"baz"'),
                    'secondArgument' => array('operator' => '==', 'value' => 'TRUE')
                );

                $mockClassNameFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter', array(), array(), '', FALSE);
		$mockMethodNameFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter', array(), array(), '', FALSE);

		$mockSubComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockSubComposite->expects($this->at(0))->method('addFilter')->with('&&', $mockClassNameFilter);
		$mockSubComposite->expects($this->at(1))->method('addFilter')->with('&&', $mockMethodNameFilter);

		$mockComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockComposite->expects($this->once())->method('addFilter')->with('&&', $mockSubComposite);

		$this->mockObjectFactory->expects($this->at(0))->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite')->will($this->returnValue($mockSubComposite));
		$this->mockObjectFactory->expects($this->at(1))->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter', 'Foo')->will($this->returnValue($mockClassNameFilter));
		$this->mockObjectFactory->expects($this->at(2))->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutMethodNameFilter', 'Bar', null, $conditions)->will($this->returnValue($mockMethodNameFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('getArgumentConstraintsFromMethodArgumentsPattern'), array(), '', FALSE);
                $parser->expects($this->once())->method('getArgumentConstraintsFromMethodArgumentsPattern')->with('firstArgument != "baz", secondArgument == TRUE')->will($this->returnValue($conditions));
                $parser->injectObjectFactory($this->mockObjectFactory);

                $parser->_call('parseDesignatorMethod', '&&', 'Foo->Bar(firstArgument != "baz", secondArgument == TRUE)', $mockComposite);
	}

        /**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getArgumentConstraintsFromMethodArgumentsPatternWorks() {
                $methodArgumentsPattern = 'arg1 == "blub,ber",   arg2 != FALSE  ,arg3 in   (TRUE, some.object.access, "fa,sel", \'blub\'), arg4 contains FALSE,arg2==TRUE';

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
                                                )
                                            );

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
                $result = $parser->_call('getArgumentConstraintsFromMethodArgumentsPattern', $methodArgumentsPattern);

                $this->assertEquals($expectedConditions, $result, 'The argument condition string has not been parsed as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorWithinAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutClassTypeFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutClassTypeFilter', 'Bar')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorWithin', '&&', 'Bar', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorPointcutAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilter', '\Foo\Bar', 'baz')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar->baz', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorPointcutThrowsAnExceptionIfTheExpressionLacksTheAspectClassMethodArrow() {
		$mockComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar', $mockComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorFilterAddsACustomFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->_call('parseDesignatorFilter', '&&', 'F3\Foo\Custom\Filter', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorFilterThrowsAnExceptionIfACustomFilterDoesNotImplementThePointcutFilterInterface() {
		$mockFilter = new \ArrayObject();
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->_call('parseDesignatorFilter', '&&', 'F3\Foo\Custom\Filter', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseRuntimeEvaluationsBasicallyWorks() {
		$expectedRuntimeEvaluationsDefinition = array(
			'&&' => array (
				'evaluateConditions' => array(
					'parsed constraints'
				)
			)
		);

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('setGlobalRuntimeEvaluationsDefinition')->with($expectedRuntimeEvaluationsDefinition);

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('getRuntimeEvaluationConditionsFromEvaluateString'), array(), '', FALSE);
		$parser->expects($this->once())->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('some == constraint')->will($this->returnValue(array('parsed constraints')));

		$parser->_call('parseRuntimeEvaluations', '&&', 'some == constraint', $mockPointcutFilterComposite);
	}


	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
			)
		);

		$evaluateString = '"blub" == 5, current.party.name <= \'foo\', this.attendee.name != current.party.person.name, this.some.object in (TRUE, some.object.access)';

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$result = $parser->_call('getRuntimeEvaluationConditionsFromEvaluateString', $evaluateString);

		$this->assertEquals($result, $expectedRuntimeEvaluationsDefinition, 'The string has not been parsed correctly.');
	}
}
?>