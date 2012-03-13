<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Policy;

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
 * Testcase for the policy expression parser
 *
 */
class PolicyExpressionParserTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 */
	public function parseMethodResourcesThrowsAnExceptionIfAResourceReferencesAnUndefinedResource() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(TYPO3\Foo\BasicClass->setSomeProperty()) || notExistingResource',
		);

		$parser =new \TYPO3\FLOW3\Security\Policy\PolicyExpressionParser();

		$parser->parseMethodResources('theOneAndOnlyResource', $resourcesTree);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\CircularResourceDefinitionDetectedException
	 */
	public function parseMethodResourcesThrowsAnExceptionIfTheResourceTreeContainsCircularReferences() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(TYPO3\TestPackage\BasicClass->setSomeProperty()) || theIntegrativeResource',
			'theOtherLonelyResource' => 'method(TYPO3\TestPackage\BasicClassValidator->.*())',
			'theIntegrativeResource' => 'theOneAndOnlyResource || theLonelyResource',

		);

		$parser =new \TYPO3\FLOW3\Security\Policy\PolicyExpressionParser();

		$parser->parseMethodResources('theIntegrativeResource', $resourcesTree);
	}

	/**
	 * @test
	 */
	public function parseMethodResourcesStoresTheCorrectResourceTreeTraceInTheTraceParameter() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(TYPO3\TestPackage\BasicClass->setSomeProperty())',
			'theOtherLonelyResource' => 'theOneAndOnlyResource',
			'theIntegrativeResource' => 'theOtherLonelyResource',

		);

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('create')->will($this->returnValue($mockPointcutFilterComposite));
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface')));

		$parser = new \TYPO3\FLOW3\Security\Policy\PolicyExpressionParser();
		$parser->injectObjectManager($mockObjectManager);
		$parser->injectReflectionService(($this->getMock('TYPO3\FLOW3\Reflection\ReflectionService')));

		$trace = array();
		$parser->parseMethodResources('theIntegrativeResource', $resourcesTree, $trace);

		$expectedTrace = array('theIntegrativeResource', 'theOtherLonelyResource', 'theOneAndOnlyResource');

		$this->assertEquals($expectedTrace, $trace, 'The trace has not been set as expected.');
	}

	/**
	 * @test
	 */
	public function parseEntityResourcesCallsParseSingleEntityResourceForEachResourceEntryOfAnEntityAndPassesTheCorrectResourceTree() {
		$resourcesTree = array(
			'TYPO3\Party\Domain\Model\Account' => array(
				'resource1' => 'someConstraint1',
				'resource2' => 'someConstraint2',
				'resource3' => 'someConstraint3',
			),
			'TYPO3\Party\Domain\Model\AbstractParty' => array(
				'anotherResource1' => 'someOtherConstraint1',
				'anotherResource2' => 'someOtherConstraint2',
				'anotherResource3' => 'someOtherConstraint3',
			)
		);

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array('parseSingleEntityResource'), array(), '', FALSE);

		$parser->expects($this->at(0))->method('parseSingleEntityResource')->with('resource1', $resourcesTree['TYPO3\Party\Domain\Model\Account'])->will($this->returnValue('parsedConstraint1'));
		$parser->expects($this->at(1))->method('parseSingleEntityResource')->with('resource2', $resourcesTree['TYPO3\Party\Domain\Model\Account'])->will($this->returnValue('parsedConstraint2'));
		$parser->expects($this->at(2))->method('parseSingleEntityResource')->with('resource3', $resourcesTree['TYPO3\Party\Domain\Model\Account'])->will($this->returnValue('parsedConstraint3'));
		$parser->expects($this->at(3))->method('parseSingleEntityResource')->with('anotherResource1', $resourcesTree['TYPO3\Party\Domain\Model\AbstractParty'])->will($this->returnValue('parsedConstraint4'));
		$parser->expects($this->at(4))->method('parseSingleEntityResource')->with('anotherResource2', $resourcesTree['TYPO3\Party\Domain\Model\AbstractParty'])->will($this->returnValue('parsedConstraint5'));
		$parser->expects($this->at(5))->method('parseSingleEntityResource')->with('anotherResource3', $resourcesTree['TYPO3\Party\Domain\Model\AbstractParty'])->will($this->returnValue('parsedConstraint6'));

		$result = $parser->parseEntityResources($resourcesTree);

		$expectedResult = array(
			'TYPO3\Party\Domain\Model\Account' => array(
				'resource1' => 'parsedConstraint1',
				'resource2' => 'parsedConstraint2',
				'resource3' => 'parsedConstraint3',
			),
			'TYPO3\Party\Domain\Model\AbstractParty' => array(
				'anotherResource1' => 'parsedConstraint4',
				'anotherResource2' => 'parsedConstraint5',
				'anotherResource3' => 'parsedConstraint6',
			)
		);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 */
	public function parseSingleEntityResourceCallsGetRuntimeEvaluationConditionsFromEvaluateStringAndReturnsAnAppropriateConstraintsArray() {
		$resourcesTree = array(
			'ownAccount' => 'this.party == current.party && this.credentialsSourec != \'foo\''
		);

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array('getRuntimeEvaluationConditionsFromEvaluateString'), array(), '', FALSE);
		$parser->expects($this->at(0))->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('this.party == current.party')->will($this->returnValue(array('firstConstraint')));
		$parser->expects($this->at(1))->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('this.credentialsSourec != \'foo\'')->will($this->returnValue(array('secondConstraint')));

		$result = $parser->_call('parseSingleEntityResource', 'ownAccount', $resourcesTree);

		$expectedResult = array(
							'&&' => array('firstConstraint', 'secondConstraint')
						);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 */
	public function parseSingleEntityResourceCallsItselfRecursivelyForReferenceResourcesInAConstraintExpression() {
		$resourcesTree = array(
			'ownAccount' => 'this.party == current.party && someOtherResource || this.credentialsSourec != \'foo\' && this.account == current.account',
			'someOtherResource' => 'this.someProperty != \'bar\''
		);

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array('getRuntimeEvaluationConditionsFromEvaluateString'), array(), '', FALSE);
		$parser->expects($this->at(0))->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('this.party == current.party')->will($this->returnValue(array('firstConstraint')));
		$parser->expects($this->at(1))->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('this.someProperty != \'bar\'')->will($this->returnValue(array('thirdConstraint')));
		$parser->expects($this->at(2))->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('this.credentialsSourec != \'foo\'')->will($this->returnValue(array('secondConstraint')));
		$parser->expects($this->at(3))->method('getRuntimeEvaluationConditionsFromEvaluateString')->with('this.account == current.account')->will($this->returnValue(array('fourthConstraint')));

		$result = $parser->_call('parseSingleEntityResource', 'ownAccount', $resourcesTree);

		$expectedResult = array(
			'&&' => array(
				'firstConstraint',
				'subConstraints' => array(
					'&&' => array('thirdConstraint')
				),
				'fourthConstraint'
			),
			'||' => array('secondConstraint')
		);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException
	 */
	public function parseSingleEntityResourceThrowsAnExceptionIfAnExpressionContainsAReferenceToANotExistingResource() {
		$resourcesTree = array(
			'ownAccount' => 'this.party == current.party && someNotExistingResource || this.credentialsSourec != \'foo\'',
		);

		$parser = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array('getRuntimeEvaluationConditionsFromEvaluateString'), array(), '', FALSE);
		$parser->expects($this->any())->method('getRuntimeEvaluationConditionsFromEvaluateString')->will($this->returnValue(array()));

		$parser->_call('parseSingleEntityResource', 'ownAccount', $resourcesTree);
	}
}

?>
