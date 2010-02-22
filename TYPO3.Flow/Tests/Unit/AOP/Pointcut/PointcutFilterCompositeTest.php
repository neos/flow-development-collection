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
 * Testcase for the Pointcut Filter Composite
 *
 * @version $Id: PointcutSettingFilterTest.php 3643 2010-01-15 14:38:07Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PointcutFilterCompositeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsDefintionReturnsTheEvaluationsFromAllContainedFiltersThatMatchedThePointcutWithTheCorrectOperators() {
		$runtimeEvaluations1 = array('methodArgumentConstraint' => array('arg1' => 'eval1'));
		$runtimeEvaluations2 = array('methodArgumentConstraint' => array('arg2' => 'eval2'));
		$runtimeEvaluations3 = array('methodArgumentConstraint' => array('arg3' => 'eval3'));
		$runtimeEvaluations4 = array('methodArgumentConstraint' => array('arg4' => 'eval4'));
		$runtimeEvaluations5 = array('methodArgumentConstraint' => array('arg5' => 'eval5', 'arg6' => 'eval6'));

		$mockPointcutFilter1 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations1));
		$mockPointcutFilter1->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter1->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter2 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations2));
		$mockPointcutFilter2->expects($this->any())->method('matches')->will($this->returnValue(FALSE));
		$mockPointcutFilter2->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter3 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter3->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations3));
		$mockPointcutFilter3->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter3->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter4 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter4->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations4));
		$mockPointcutFilter4->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter4->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter5 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter5->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations5));
		$mockPointcutFilter5->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter5->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$pointcutFilterComposite = new PointcutFilterComposite();
		$pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
		$pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);
		$pointcutFilterComposite->addFilter('||', $mockPointcutFilter3);
		$pointcutFilterComposite->addFilter('||!', $mockPointcutFilter4);
		$pointcutFilterComposite->addFilter('||!', $mockPointcutFilter5);

		$expectedRuntimeEvaluations = array(
			'&&' => array(
				'methodArgumentConstraint' => array('arg1' => 'eval1')
			),
			'||' => array(
				'methodArgumentConstraint' => array('arg3' => 'eval3')
			),
			'||!' => array(
				'methodArgumentConstraint' => array('arg4' => 'eval4', 'arg5' => 'eval5', 'arg6' => 'eval6')
			)
		);

		$pointcutFilterComposite->matches('className', 'methodName', 'methodDeclaringClassName', 1);

		$this->assertEquals($expectedRuntimeEvaluations, $pointcutFilterComposite->getRuntimeEvaluationsDefinition());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchesReturnsTrueForNegatedSubfiltersWithRuntimeEvaluations() {
		$mockPointcutFilter1 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter1->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter2 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter2->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter3 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter3->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter3->expects($this->any())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter4 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter4->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter4->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$pointcutFilterComposite = new PointcutFilterComposite();
		$pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
		$pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);
		$pointcutFilterComposite->addFilter('||', $mockPointcutFilter3);
		$pointcutFilterComposite->addFilter('||!', $mockPointcutFilter4);

		$this->assertTrue($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function globalRuntimeEvaluationsDefinitionAreAddedCorrectlyToThePointcutFilterComposite() {
		$existingRuntimeEvaluationsDefintion = array (
												'&&' => array (
													'&&' => array (
														'methodArgumentConstraints' => array (
															'usage' => array (
																'operator' => 'in',
																'value' => array ('\'usage1\'', '\'usage2\'', '"usage3"')
															)
														)
													)
												)
											);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $existingRuntimeEvaluationsDefintion);

		$newRuntimeEvaluationsDefinition = array (
												'&&' => array (
													'evaluateConditions' => array (
														array(
															'operator' => '==',
															'leftValue' => '"bar"',
															'rightValue' => 4,
														)
													)
												)
											);

		$pointcutFilterComposite->setGlobalRuntimeEvaluationsDefinition($newRuntimeEvaluationsDefinition);

		$expectedResult = array (
							'&&' => array (
								'&&' => array (
									'methodArgumentConstraints' => array (
										'usage' => array (
											'operator' => 'in',
											'value' => array ('\'usage1\'', '\'usage2\'', '"usage3"')
										)
									)
								),
								'evaluateConditions' => array (
									array (
										'operator' => '==',
										'leftValue' => '"bar"',
										'rightValue' => 4,
									)
								)
							)
						);

		$this->assertEquals($expectedResult, $pointcutFilterComposite->getRuntimeEvaluationsDefinition(), 'The runtime evaluations definition has not been added correctly to the pointcut filter composite.');
	}
	
	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsClosureCodeReturnsTheCorrectStringForBasicRuntimeEvaluationsDefintion() {
		$runtimeEvaluationsDefintion = array (
										'&&' => array (
											'&&' => array (
												'&&' => array (
													'evaluateConditions' => array (
														0 => array (
															'operator' => '!=',
															'leftValue' => 'this.some.thing',
															'rightValue' => 'current.party.name',
													)),
													'&&' => array (
														'methodArgumentConstraints' => array (
															'identifier' => array (
																'operator' => array (
																	0 => '>',
																	1 => '<='
																),
																'value' => array (
																	0 => '3',
																	1 => '5'
										))))))),
										'||' => array (
											'&&' => array (
												'methodArgumentConstraints' => array (
													'identifier' => array (
														'operator' => array('=='),
														'value' => array('42')
									)))));

		$expectedResult = "\n\t\t\t\t\t\tfunction(\\F3\\FLOW3\\AOP\\JoinPointInterface \$joinPoint) use (\$currentObject, \$objectManager) {\n" .
								"\t\t\t\t\t\t\t\$party = \$objectManager->get('F3\\FLOW3\\Security\\ContextHolderInterface')->getContext()->getParty();\n" .
								"\t\t\t\t\t\t\treturn (((F3\FLOW3\Reflection\ObjectAccess::getPropertyPath(\$currentObject, 'some.thing') != F3\FLOW3\Reflection\ObjectAccess::getPropertyPath(\$party, 'name')) && (\$joinPoint->getMethodArgument('identifier') > 3 && \$joinPoint->getMethodArgument('identifier') <= 5)) || (\$joinPoint->getMethodArgument('identifier') == 42));\n" .
								"\t\t\t\t\t\t}";

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $runtimeEvaluationsDefintion);

		$result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

		$this->assertTrue($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

		/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsClosureCodeHandlesDefinitionsConcatenatedByNegatedOperatorsCorrectly() {
		$runtimeEvaluationsDefintion = array (
										'&&' => array (
											'&&' => array (
												'&&' => array (
													'evaluateConditions' => array (
														0 => array (
															'operator' => '!=',
															'leftValue' => 'this.some.thing',
															'rightValue' => 'current.party.name',
													)),
													'&&!' => array (
														'methodArgumentConstraints' => array (
															'identifier' => array (
																'operator' => array (
																	0 => '>',
																	1 => '<='
																),
																'value' => array (
																	0 => '3',
																	1 => '5'
										))))))),
										'||!' => array (
											'&&' => array (
												'methodArgumentConstraints' => array (
													'identifier' => array (
														'operator' => array('=='),
														'value' => array('42')
									)))));

		$expectedResult = "\n\t\t\t\t\t\tfunction(\\F3\\FLOW3\\AOP\\JoinPointInterface \$joinPoint) use (\$currentObject, \$objectManager) {\n" .
								"\t\t\t\t\t\t\t\$party = \$objectManager->get('F3\\FLOW3\\Security\\ContextHolderInterface')->getContext()->getParty();\n" .
								"\t\t\t\t\t\t\treturn (((F3\FLOW3\Reflection\ObjectAccess::getPropertyPath(\$currentObject, 'some.thing') != F3\FLOW3\Reflection\ObjectAccess::getPropertyPath(\$party, 'name')) && (!(\$joinPoint->getMethodArgument('identifier') > 3 && \$joinPoint->getMethodArgument('identifier') <= 5))) || (!(\$joinPoint->getMethodArgument('identifier') == 42)));\n" .
								"\t\t\t\t\t\t}";

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $runtimeEvaluationsDefintion);

		$result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

		$this->assertTrue($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsClosureCodeReturnsTheCorrectStringForAnEmptyDefinition() {
		$expectedResult = 'NULL';

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', array());

		$result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

		$this->assertFalse($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAnArgumentWithMoreThanOneCondition() {
		$condition = array (
								'identifier' => array (
									'operator' => array (
										0 => '>',
										1 => '<='
									),
									'value' => array (
										0 => '3',
										1 => '5'
									)
								)
							);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

		$expectedResult = '($joinPoint->getMethodArgument(\'identifier\') > 3 && $joinPoint->getMethodArgument(\'identifier\') <= 5)';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAConditionWithObjectAccess() {
		$condition = array (
								'identifier' => array (
									'operator' => array (
										0 => '==',
										1 => '!='
									),
									'value' => array (
										0 => 'this.bar.baz',
										1 => 'current.party.bar.baz'
									)
								),
								'some.object.property' => array (
									'operator' => array (
										0 => '!='
									),
									'value' => array (
										0 => 'this.object.with.another.property'
									)
								)
							);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

		$expectedResult = '($joinPoint->getMethodArgument(\'identifier\') == F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($currentObject, \'bar.baz\') && $joinPoint->getMethodArgument(\'identifier\') != F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($party, \'bar.baz\') && F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($joinPoint->getMethodArgument(\'some\'), \'object.property\') != F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($currentObject, \'object.with.another.property\'))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAConditionWithInOperator() {
		$condition = array (
								'identifier' => array (
									'operator' => array (
										0 => 'in',
									),
									'value' => array (
										0 => array ('\'usage1\'', '\'usage2\'', '"usage3"'),
									)
								)
							);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

		$expectedResult = '(in_array($joinPoint->getMethodArgument(\'identifier\'), array(\'usage1\', \'usage2\', "usage3")))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildGlobalRuntimeEvaluationsConditionCodeBuildsTheCorrectCodeForConditionsWithObjectAccess() {
		$condition = array (
							0 => array (
								'operator' => '!=',
								'leftValue' => 'this.some.thing',
								'rightValue' => 'current.party.name',
							),
							1 => array (
								'operator' => '==',
								'leftValue' => 'current.party.account.accountIdentifier',
								'rightValue' => '"admin"',
							)
						);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

		$expectedResult = '(F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\') != F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($party, \'name\') && F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($party, \'account.accountIdentifier\') == "admin")';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildGlobalRuntimeEvaluationsConditionCodeBuildsTheCorrectCodeForAConditionWithInOperator() {
		$condition = array (
								0 => array (
									'operator' => 'in',
									'leftValue' => 'this.some.thing',
									'rightValue' => array('"foo"', 'current.party.name', 5),
								)
							);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

		$expectedResult = '(in_array(F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\'), array("foo", F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($party, \'name\'), 5)))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}
}
?>