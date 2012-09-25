<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Pointcut;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Pointcut Filter Composite
 *
 */
class PointcutFilterCompositeTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getRuntimeEvaluationsDefintionReturnsTheEvaluationsFromAllContainedFiltersThatMatchedThePointcutWithTheCorrectOperators() {
		$runtimeEvaluations1 = array('methodArgumentConstraint' => array('arg1' => 'eval1'));
		$runtimeEvaluations2 = array('methodArgumentConstraint' => array('arg2' => 'eval2'));
		$runtimeEvaluations3 = array('methodArgumentConstraint' => array('arg3' => 'eval3'));
		$runtimeEvaluations4 = array('methodArgumentConstraint' => array('arg4' => 'eval4'));
		$runtimeEvaluations5 = array('methodArgumentConstraint' => array('arg5' => 'eval5', 'arg6' => 'eval6'));

		$mockPointcutFilter1 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations1));
		$mockPointcutFilter1->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter1->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter2 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations2));
		$mockPointcutFilter2->expects($this->any())->method('matches')->will($this->returnValue(FALSE));
		$mockPointcutFilter2->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter3 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter3->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations3));
		$mockPointcutFilter3->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter3->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter4 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter4->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations4));
		$mockPointcutFilter4->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter4->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$mockPointcutFilter5 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter5->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue($runtimeEvaluations5));
		$mockPointcutFilter5->expects($this->any())->method('matches')->will($this->returnValue(TRUE));
		$mockPointcutFilter5->expects($this->any())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));

		$pointcutFilterComposite = new \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite();
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
	 */
	public function matchesReturnsTrueForNegatedSubfiltersWithRuntimeEvaluations() {
		$mockPointcutFilter1 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter1->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter2 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter2->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter3 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter3->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter3->expects($this->any())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter4 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter4->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter4->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$pointcutFilterComposite = new \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite();
		$pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
		$pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);
		$pointcutFilterComposite->addFilter('||', $mockPointcutFilter3);
		$pointcutFilterComposite->addFilter('||!', $mockPointcutFilter4);

		$this->assertTrue($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
	}

	/**
	 * @test
	 */
	public function matchesReturnsTrueForNegatedSubfilter() {
		$mockPointcutFilter1 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter1->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter2 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter2->expects($this->once())->method('matches')->will($this->returnValue(FALSE));

		$pointcutFilterComposite = new \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite();
		$pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
		$pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);

		$this->assertTrue($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
	}

	/**
	 * @test
	 */
	public function matchesReturnsFalseEarlyForAndedSubfilters() {
		$mockPointcutFilter1 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter1->expects($this->once())->method('matches')->will($this->returnValue(FALSE));

		$mockPointcutFilter2 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter2->expects($this->never())->method('matches')->will($this->returnValue(FALSE));

		$pointcutFilterComposite = new \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite();
		$pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
		$pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);

		$this->assertFalse($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
	}

	/**
	 * @test
	 */
	public function matchesReturnsFalseEarlyForAndedNegatedSubfilters() {
		$mockPointcutFilter1 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter1->expects($this->once())->method('matches')->will($this->returnValue(TRUE));

		$mockPointcutFilter2 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->any())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('eval')));
		$mockPointcutFilter2->expects($this->never())->method('matches')->will($this->returnValue(TRUE));

		$pointcutFilterComposite = new \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite();
		$pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter1);
		$pointcutFilterComposite->addFilter('&&', $mockPointcutFilter2);

		$this->assertFalse($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
	}

	/**
	 * @test
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

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
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

		$expectedResult = "\n\t\t\t\t\t\tfunction(\\TYPO3\\Flow\\Aop\\JoinPointInterface \$joinPoint) use (\$objectManager) {\n" .
								"\t\t\t\t\t\t\t\$currentObject = \$joinPoint->getProxy();\n" .
								"\t\t\t\t\t\t\t\$globalObjectNames = \$objectManager->getSettingsByPath(array('TYPO3', 'Flow', 'aop', 'globalObjects'));\n" .
								"\t\t\t\t\t\t\t\$globalObjects = array_map(function(\$objectName) use (\$objectManager) { return \$objectManager->get(\$objectName); }, \$globalObjectNames);\n" .
								"\t\t\t\t\t\t\treturn (((\TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath(\$currentObject, 'some.thing') != \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath(\$globalObjects['party'], 'name')) && (\$joinPoint->getMethodArgument('identifier') > 3 && \$joinPoint->getMethodArgument('identifier') <= 5)) || (\$joinPoint->getMethodArgument('identifier') == 42));\n" .
								"\t\t\t\t\t\t}";

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $runtimeEvaluationsDefintion);

		$result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

		$this->assertTrue($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
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

		$expectedResult = "\n\t\t\t\t\t\tfunction(\\TYPO3\\Flow\\Aop\\JoinPointInterface \$joinPoint) use (\$objectManager) {\n" .
								"\t\t\t\t\t\t\t\$currentObject = \$joinPoint->getProxy();\n" .
								"\t\t\t\t\t\t\t\$globalObjectNames = \$objectManager->getSettingsByPath(array('TYPO3', 'Flow', 'aop', 'globalObjects'));\n" .
								"\t\t\t\t\t\t\t\$globalObjects = array_map(function(\$objectName) use (\$objectManager) { return \$objectManager->get(\$objectName); }, \$globalObjectNames);\n" .
								"\t\t\t\t\t\t\treturn (((\TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath(\$currentObject, 'some.thing') != \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath(\$globalObjects['party'], 'name')) && (!(\$joinPoint->getMethodArgument('identifier') > 3 && \$joinPoint->getMethodArgument('identifier') <= 5))) || (!(\$joinPoint->getMethodArgument('identifier') == 42)));\n" .
								"\t\t\t\t\t\t}";

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $runtimeEvaluationsDefintion);

		$result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

		$this->assertTrue($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 */
	public function getRuntimeEvaluationsClosureCodeReturnsTheCorrectStringForAnEmptyDefinition() {
		$expectedResult = 'NULL';

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', array());

		$result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

		$this->assertFalse($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
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

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

		$expectedResult = '($joinPoint->getMethodArgument(\'identifier\') > 3 && $joinPoint->getMethodArgument(\'identifier\') <= 5)';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
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

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

		$expectedResult = '($joinPoint->getMethodArgument(\'identifier\') == \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'bar.baz\') && $joinPoint->getMethodArgument(\'identifier\') != \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'bar.baz\') && \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($joinPoint->getMethodArgument(\'some\'), \'object.property\') != \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'object.with.another.property\'))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
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

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

		$expectedResult = '((array(\'usage1\', \'usage2\', "usage3") instanceof \SplObjectStorage || array(\'usage1\', \'usage2\', "usage3") instanceof \Doctrine\Common\Collections\Collection ? $joinPoint->getMethodArgument(\'identifier\') !== NULL && array(\'usage1\', \'usage2\', "usage3")->contains($joinPoint->getMethodArgument(\'identifier\')) : in_array($joinPoint->getMethodArgument(\'identifier\'), array(\'usage1\', \'usage2\', "usage3"))))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 */
	public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAConditionWithMatchesOperator() {
		$condition = array (
								'identifier' => array (
									'operator' => array (
										0 => 'matches',
										1 => 'matches'
									),
									'value' => array (
										0 => array ('\'usage1\'', '\'usage2\'', '"usage3"'),
										1 => 'this.accounts'
									)
								)
							);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

		$expectedResult = '((!empty(array_intersect($joinPoint->getMethodArgument(\'identifier\'), array(\'usage1\', \'usage2\', "usage3")))) && (!empty(array_intersect($joinPoint->getMethodArgument(\'identifier\'), \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'accounts\')))))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
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

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

		$expectedResult = '(\TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\') != \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\') && \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'account.accountIdentifier\') == "admin")';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 */
	public function buildGlobalRuntimeEvaluationsConditionCodeBuildsTheCorrectCodeForAConditionWithInOperator() {
		$condition = array (
								0 => array (
									'operator' => 'in',
									'leftValue' => 'this.some.thing',
									'rightValue' => array('"foo"', 'current.party.name', 5),
								)
							);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

		$expectedResult = '((array("foo", \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5) instanceof \SplObjectStorage || array("foo", \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5) instanceof \Doctrine\Common\Collections\Collection ? \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\') !== NULL && array("foo", \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5)->contains(\TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\')) : in_array(\TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\'), array("foo", \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5))))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 */
	public function buildGlobalRuntimeEvaluationsConditionCodeBuildsTheCorrectCodeForAConditionWithMatchesOperator() {
		$condition = array (
								0 => array (
									'operator' => 'matches',
									'leftValue' => 'this.some.thing',
									'rightValue' => array('"foo"', 'current.party.name', 5),
								),
								1 => array (
									'operator' => 'matches',
									'leftValue' => 'this.some.thing',
									'rightValue' => 'current.party.accounts',
								)
							);

		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);

		$result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

		$expectedResult = '((!empty(array_intersect(\TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\'), array("foo", \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5)))) && (!empty(array_intersect(\TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($currentObject, \'some.thing\'), \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'accounts\')))))';

		$this->assertEquals($expectedResult, $result, 'The wrong Code has been built.');
	}

	/**
	 * @test
	 */
	public function hasRuntimeEvaluationsDefinitionConsidersGlobalAndFilterRuntimeEvaluationsDefinitions() {
		$pointcutFilterComposite = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite'), array('dummy'), array(), '', FALSE);
		$this->assertFalse($pointcutFilterComposite->hasRuntimeEvaluationsDefinition());

		$pointcutFilterComposite->_set('globalRuntimeEvaluationsDefinition', array('foo', 'bar'));
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', array());
		$this->assertTrue($pointcutFilterComposite->hasRuntimeEvaluationsDefinition());

		$pointcutFilterComposite->_set('globalRuntimeEvaluationsDefinition', array());
		$pointcutFilterComposite->_set('runtimeEvaluationsDefinition', array('bar'));
		$this->assertTrue($pointcutFilterComposite->hasRuntimeEvaluationsDefinition());

	}

	/**
	 * @test
	 */
	public function reduceTargetClassNamesFiltersAllClassesNotMatchedAByClassNameFilter() {
		$availableClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Class2',
			'TestPackage\Subpackage\SubSubPackage\Class3',
			'TestPackage\Subpackage2\Class4'
		);
		sort($availableClassNames);
		$availableClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
		$availableClassNamesIndex->setClassNames($availableClassNames);

		$classNameFilter1 = new \TYPO3\Flow\Aop\Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\SubSubPackage\Class3');
		$classNameFilter2 = new \TYPO3\Flow\Aop\Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\Class1');
		$methodNameFilter1 = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodNameFilter('method2');

		$expectedClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Subpackage\SubSubPackage\Class3'
		);
		sort($expectedClassNames);
		$expectedClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
		$expectedClassNamesIndex->setClassNames($expectedClassNames);

		$pointcutFilterComposite = new \TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite();
		$pointcutFilterComposite->addFilter('&&', $classNameFilter1);
		$pointcutFilterComposite->addFilter('||', $classNameFilter2);
		$pointcutFilterComposite->addFilter('&&', $methodNameFilter1);

		$result = $pointcutFilterComposite->reduceTargetClassNames($availableClassNamesIndex);

		$this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
	}
}
?>