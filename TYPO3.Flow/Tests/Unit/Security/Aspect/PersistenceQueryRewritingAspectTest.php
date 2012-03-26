<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Aspect;

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
 * Testcase for the persistence query rewriting aspect
 *
 * @covers TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect
 */
class PersistenceQueryRewritingAspectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function rewriteQomQueryAddsTheConstraintsGivenByThePolicyServiceCorrectlyToTheQueryObject() {
		$entityType = 'MyClass';

		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->atLeastOnce())->method('getConstraint')->will($this->returnValue('existingConstraint'));
		$mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));
		$mockQuery->expects($this->once())->method('logicalNot')->with('newConstraints')->will($this->returnValue('newConstraintsNegated'));
		$mockQuery->expects($this->once())->method('logicalAnd')->with('existingConstraint', 'newConstraintsNegated')->will($this->returnValue('mergedResultConstraints'));
		$mockQuery->expects($this->once())->method('matching')->with('mergedResultConstraints');

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

		$roles = array('role1', 'role2');

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($roles));
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityType, $roles)->will($this->returnValue(array('parsedConstraints')));
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForConstraintDefinitions'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getQomConstraintForConstraintDefinitions')->with(array('parsedConstraints'), $mockQuery)->will($this->returnValue('newConstraints'));
		$rewritingAspect->_set('policyService', $mockPolicyService);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function rewriteQomQueryUsesTheConstraintsGivenByThePolicyServiceInTheQueryObject() {
		$entityType = 'MyClass';

		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->atLeastOnce())->method('getConstraint')->will($this->returnValue(NULL));
		$mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));
		$mockQuery->expects($this->once())->method('logicalNot')->with('newConstraints')->will($this->returnValue('newConstraintsNegated'));
		$mockQuery->expects($this->never())->method('logicalAnd');
		$mockQuery->expects($this->once())->method('matching')->with('newConstraintsNegated');

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

		$roles = array('role1', 'role2');

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($roles));
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityType, $roles)->will($this->returnValue(array('parsedConstraints')));
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForConstraintDefinitions'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getQomConstraintForConstraintDefinitions')->with(array('parsedConstraints'), $mockQuery)->will($this->returnValue('newConstraints'));
		$rewritingAspect->_set('policyService', $mockPolicyService);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function rewriteQomQueryDoesNotChangeTheOriginalQueryConstraintsIfThereIsAPolicyEntryButNoAdditionalConstraintsAreNeededInTheCurrentSituation() {
		$entityType = 'MyClass';

		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));
		$mockQuery->expects($this->never())->method('matching');

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

		$roles = array('role1', 'role2');

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($roles));
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityType, $roles)->will($this->returnValue(array('parsedConstraints')));
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForConstraintDefinitions'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getQomConstraintForConstraintDefinitions')->with(array('parsedConstraints'), $mockQuery)->will($this->returnValue(NULL));
		$rewritingAspect->_set('policyService', $mockPolicyService);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function rewriteQomQueryDoesNotRewriteQueryIfSecurityContextIsNotInitialized() {
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(FALSE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function aQomQueryIsNotRewrittenIfThereIsNoPolicyEntryForItsEntityType() {
		$entityType = 'MyClass';

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(FALSE));

		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->_set('policyService', $mockPolicyService);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function getQomConstraintForConstraintDefinitionsWorks() {
		$parsedConstraints = array(
			'resource' => array(
				'&&' => array(
					array('firstConstraint'),
					'subConstraints' => array(
						'&&' => array(
							array('thirdConstraint')
						)
					),
					array('fourthConstraint')
				),
				'||' => array(
					array('secondConstraint')
				),
				'&&!' => array(
					array('fifthConstraint')
				),
				'||!' => array(
					array('sixthConstraint')
				)
			)
		);

		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->at(0))->method('logicalAnd')->with('firstConstraintResult', 'thirdConstraintResult')->will($this->returnValue('firstAndThird'));
		$mockQuery->expects($this->at(1))->method('logicalAnd')->with('firstAndThird', 'fourthConstraintResult')->will($this->returnValue('firstAndThirdAndFourth'));
		$mockQuery->expects($this->at(2))->method('logicalOr')->with('firstAndThirdAndFourth', 'secondConstraintResult')->will($this->returnValue('firstAndThirdAndFourthOrSecond'));
		$mockQuery->expects($this->at(3))->method('logicalNot')->with('fifthConstraintResult')->will($this->returnValue('notFifth'));
		$mockQuery->expects($this->at(4))->method('logicalAnd')->with('firstAndThirdAndFourthOrSecond', 'notFifth')->will($this->returnValue('firstAndThirdAndFourthOrSecondAndNotFifth'));
		$mockQuery->expects($this->at(5))->method('logicalNot')->with('sixthConstraintResult')->will($this->returnValue('notSixth'));
		$mockQuery->expects($this->at(6))->method('logicalOr')->with('firstAndThirdAndFourthOrSecondAndNotFifth', 'notSixth')->will($this->returnValue('firstAndThirdAndFourthOrSecondAndNotFifthOrNotSixth'));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForSingleConstraintDefinition'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('getQomConstraintForSingleConstraintDefinition')->with(array('firstConstraint'), $mockQuery)->will($this->returnValue('firstConstraintResult'));
		$rewritingAspect->expects($this->at(1))->method('getQomConstraintForSingleConstraintDefinition')->with(array('thirdConstraint'), $mockQuery)->will($this->returnValue('thirdConstraintResult'));
		$rewritingAspect->expects($this->at(2))->method('getQomConstraintForSingleConstraintDefinition')->with(array('fourthConstraint'), $mockQuery)->will($this->returnValue('fourthConstraintResult'));
		$rewritingAspect->expects($this->at(3))->method('getQomConstraintForSingleConstraintDefinition')->with(array('secondConstraint'), $mockQuery)->will($this->returnValue('secondConstraintResult'));
		$rewritingAspect->expects($this->at(4))->method('getQomConstraintForSingleConstraintDefinition')->with(array('fifthConstraint'), $mockQuery)->will($this->returnValue('fifthConstraintResult'));
		$rewritingAspect->expects($this->at(5))->method('getQomConstraintForSingleConstraintDefinition')->with(array('sixthConstraint'), $mockQuery)->will($this->returnValue('sixthConstraintResult'));

		$result = $rewritingAspect->_call('getQomConstraintForConstraintDefinitions', $parsedConstraints, $mockQuery);

		$this->assertEquals($result, 'firstAndThirdAndFourthOrSecondAndNotFifthOrNotSixth', 'The query constraints have not been created correctly.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	public function getQomConstraintForSingleConstraintDefinitionThrowsAnExceptionIfAConstraintHasNoReferenceToTheCurrentObjectIndicatedByTheThisKeyword() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');

		$constraint = array(
			'operator' => '==',
			'leftValue' => 'current.party',
			'rightValue' =>  'current.some.other.object'
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);
	}

	/**
	 * @test
	 */
	public function getQomConstraintFoSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForAnEqualityOperatorComparingASimpleValue() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('party', 'Andi')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => '==',
			'leftValue' => '"Andi"',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForGlobalObject'), array(), '', FALSE);
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForAnEqualityOperatorAccessingAGlobalObject() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => '==',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheInOperator() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('in')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => 'in',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheContainsOperator() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('contains')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => 'contains',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheMatchesOperator() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->at(0))->method('contains')->with('accounts', 1)->will($this->returnValue('constraint1'));
		$mockQuery->expects($this->at(1))->method('contains')->with('accounts', 'two')->will($this->returnValue('constraint2'));
		$mockQuery->expects($this->at(2))->method('logicalAnd')->with('constraint2', 'constraint1')->will($this->returnValue('compositeConstraint1'));
		$mockQuery->expects($this->at(3))->method('contains')->with('accounts', 3)->will($this->returnValue('constraint3'));
		$mockQuery->expects($this->at(4))->method('logicalAnd')->with('constraint3', 'compositeConstraint1')->will($this->returnValue('compositeConstraint2'));

		$constraint = array(
			'operator' => 'matches',
			'leftValue' => array(1, '"two"', 3),
			'rightValue' =>  'this.accounts'
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with(array(1, '"two"', 3))->will($this->returnValue(array(1, 'two', 3)));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('compositeConstraint2', $resultConstraint);
	}

	/**
	 * @test
	 */
	public function getValueForOperandReturnsTheCorrectValueForSimpleValues() {
		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$operandValues = array(
			'"Andi"' => 'Andi',
			'\'Andi\'' => 'Andi',
			1 => 1,
			'TRUE' => TRUE,
			'FALSE' => FALSE,
			'NULL' => NULL
		);

		foreach ($operandValues as $operand => $expectedResult) {
			$result = $rewritingAspect->_call('getValueForOperand', $operand);
			$this->assertEquals($result, $expectedResult, 'The wrong value has been returned!');
		}
	}

	/**
	 * @test
	 */
	public function getValueForOperandReturnsTheCorrectValueFromGlobalObjects() {
		$className = 'dummyParty' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' {
				public function getName() {
					return "Andi";
				}
			}
		');

		$settings = array(
			'aop' => array(
				'globalObjects' => array(
					'party' => 'new ' . $className . '();'
				)
			)
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->injectSettings($settings);

		$operand = 'current.party.name';

		$result = $rewritingAspect->_call('getValueForOperand', $operand);

		$this->assertEquals($result, 'Andi', 'The wrong value has been returned!');
	}

	/**
	 * @test
	 */
	public function getValueForOperandReturnsTheCorrectValueForArrayOperands() {
		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$operand = array(1, '"Andi"', 3, '\'Andi\'');

		$result = $rewritingAspect->_call('getValueForOperand', $operand);

		$expectedResult = array(1, 'Andi', 3, 'Andi');

		$this->assertEquals($result, $expectedResult, 'The wrong value has been returned!');
	}

	/**
	 * @test
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifierChecksTheConstraintsGivenByThePolicyServiceForTheReturnedObject() {
		$entityClassName = 'entityClass' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . ' implements \TYPO3\FLOW3\Object\Proxy\ProxyInterface {
			public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
		$result = new $entityClassName();

		$mockAdviceChain = $this->getMock('TYPO3\FLOW3\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue($result));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$roles = array('role1', 'role2');

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue($roles));
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityClassName, $roles)->will($this->returnValue(array('parsedConstraints')));
		$mockPolicyService->expects($this->any())->method('hasPolicyEntryForEntityType')->with($entityClassName)->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('checkConstraintDefinitionsOnResultObject'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('checkConstraintDefinitionsOnResultObject')->with(array('parsedConstraints'), $result)->will($this->returnValue(TRUE));
		$rewritingAspect->expects($this->at(1))->method('checkConstraintDefinitionsOnResultObject')->with(array('parsedConstraints'), $result)->will($this->returnValue(FALSE));
		$rewritingAspect->_set('policyService', $mockPolicyService);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$this->assertEquals($result, $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint));
		$this->assertEquals(NULL, $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifierFetchesTheSecurityContextOnTheFirstCallToBeSureTheSessionHasAlreadyBeenInitializedWhenTheContextIsBuilt() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->any())->method('getType')->will($this->returnValue('MyClass'));

		$mockAdviceChain = $this->getMock('TYPO3\FLOW3\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(NULL));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array()));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->will($this->returnValue(FALSE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->_set('policyService', $mockPolicyService);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
	}


	/**
	 * @test
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifierReturnsObjectIfSecurityContextIsNotInitialized() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->any())->method('getType')->will($this->returnValue('MyClass'));

		$mockAdviceChain = $this->getMock('TYPO3\FLOW3\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(NULL));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(FALSE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->_set('securityContext', $mockSecurityContext);

		$rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function checkConstraintDefinitionsOnResultObjectBasicallyWorks() {
		$parsedConstraints = array(
			'resource' => array(
				'&&' => array(
					array('firstConstraint'),
					'subConstraints' => array(
						'&&' => array(
							array('thirdConstraint')
						)
					),
					array('fourthConstraint')
				),
				'||' => array(
					array('secondConstraint')
				),
				'&&!' => array(
					array('fifthConstraint')
				),
				'||!' => array(
					array('sixthConstraint')
				)
			)
		);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('checkSingleConstraintDefinitionOnResultObject'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('firstConstraint'), array())->will($this->returnValue(FALSE));
		$rewritingAspect->expects($this->at(1))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('thirdConstraint'), array())->will($this->returnValue(FALSE));
		$rewritingAspect->expects($this->at(2))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('fourthConstraint'), array())->will($this->returnValue(TRUE));
		$rewritingAspect->expects($this->at(3))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('secondConstraint'), array())->will($this->returnValue(TRUE));
		$rewritingAspect->expects($this->at(4))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('fifthConstraint'), array())->will($this->returnValue(FALSE));
		$rewritingAspect->expects($this->at(5))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('sixthConstraint'), array())->will($this->returnValue(FALSE));

		$this->assertTrue($rewritingAspect->_call('checkConstraintDefinitionsOnResultObject', $parsedConstraints, array()));
	}

	/**
	 * @test
	 */
	public function checkSingleConstraintDefinitionOnResultObjectCallsGetObjectValueByPathForAllExpressionsStartingWithThis() {
		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with(NULL, 'accounts.title');
		$rewritingAspect->expects($this->at(2))->method('getObjectValueByPath')->with(NULL, 'accounts.title');
		$rewritingAspect->expects($this->at(4))->method('getObjectValueByPath')->with(NULL, 'accounts.title');
		$rewritingAspect->expects($this->at(5))->method('getObjectValueByPath')->with(NULL, 'party.name');

		$constraint1 = array(
			'operator' => '==',
			'leftValue' => '"blub"',
			'rightValue' =>  'this.accounts.title'
		);

		$constraint2 = array(
			'operator' => '==',
			'leftValue' =>  'this.accounts.title',
			'rightValue' => '"blub"'
		);

		$constraint3 = array(
			'operator' => '==',
			'leftValue' =>  'this.accounts.title',
			'rightValue' => 'this.party.name'
		);

		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, NULL);
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, NULL);
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint3, NULL);
	}

	/**
	 * @test
	 */
	public function checkSingleConstraintDefinitionOnResultObjectCallsGetValueForOperandForAllExpressionsNotStartingWithThis() {
		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('getValueForOperand')->with('"blub"');
		$rewritingAspect->expects($this->at(3))->method('getValueForOperand')->with('TRUE');
		$rewritingAspect->expects($this->at(5))->method('getValueForOperand')->with('NULL');

		$constraint1 = array(
			'operator' => '==',
			'leftValue' => '"blub"',
			'rightValue' =>  'this.accounts.title'
		);

		$constraint2 = array(
			'operator' => '==',
			'leftValue' =>  'this.accounts.title',
			'rightValue' => 'TRUE'
		);

		$constraint3 = array(
			'operator' => '==',
			'leftValue' =>  'this.accounts.title',
			'rightValue' => 'NULL'
		);

		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, NULL);
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, NULL);
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint3, NULL);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	public function checkSingleConstraintDefinitionOnResultObjectThrowsAnExceptionIfAConstraintHasNoReferenceToTheCurrentObjectIndicatedByTheThisKeyword() {
		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$constraint = array(
			'operator' => '==',
			'leftValue' => '"blub"',
			'rightValue' =>  'NULL'
		);

		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, NULL);
	}

	/**
	 * @test
	 */
	public function checkSingleConstraintDefinitionOnResultObjectWorksForEqualityOperators() {
		$entityClassName = 'entityClass' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . ' implements \TYPO3\FLOW3\Object\Proxy\ProxyInterface {
			public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
		$mockEntity = $this->getMock($entityClassName, array(), array(), '', FALSE);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('"blub"')->will($this->returnValue('blub'));
		$rewritingAspect->expects($this->any())->method('getObjectValueByPath')->with($mockEntity, 'accounts.title')->will($this->returnValue('blub'));

		$constraint1 = array(
			'operator' => '==',
			'leftValue' => '"blub"',
			'rightValue' =>  'this.accounts.title'
		);

		$constraint2 = array(
			'operator' => '!=',
			'leftValue' => '"blub"',
			'rightValue' =>  'this.accounts.title'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, $mockEntity));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, $mockEntity));
	}

	/**
	 * @test
	 */
	public function checkSingleConstraintDefinitionOnResultObjectWorksForTheInOperator() {
		$entityClassName = 'entityClass' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . ' implements \TYPO3\FLOW3\Object\Proxy\ProxyInterface {
			public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
		$mockEntity = $this->getMock($entityClassName, array(), array(), '', FALSE);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue('blub'));
		$rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('bla', 'blub', 'foo')));
		$rewritingAspect->expects($this->at(3))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('bla', 'foo', 'bar')));

		$constraint = array(
			'operator' => 'in',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
	}

	/**
	 * @test
	 */
	public function checkSingleConstraintDefinitionOnResultObjectWorksForTheContainsOperator() {
		$entityClassName = 'entityClass' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . ' implements \TYPO3\FLOW3\Object\Proxy\ProxyInterface {
			public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
		$mockEntity = $this->getMock($entityClassName, array(), array(), '', FALSE);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue(array('bla', 'blub', 'foo')));
		$rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue('blub'));
		$rewritingAspect->expects($this->at(3))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue('bar'));

		$constraint = array(
			'operator' => 'contains',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
	}

	/**
	 * @test
	 */
	public function checkSingleConstraintDefinitionOnResultObjectWorksForTheMatchesOperator() {
		$entityClassName = 'entityClass' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . ' implements \TYPO3\FLOW3\Object\Proxy\ProxyInterface {
			public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
		$mockEntity = $this->getMock($entityClassName, array(), array(), '', FALSE);

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue(array('bla', 'blub', 'blubber')));
		$rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('hinz', 'blub', 'kunz')));
		$rewritingAspect->expects($this->at(3))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('foo', 'bar', 'baz')));

		$constraint = array(
			'operator' => 'matches',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
	}

	/**
	 * @test
	 */
	public function checkSingleConstraintDefinitionOnResultObjectComparesTheIdentifierWhenComparingPersistedObjects() {
		$entityClassName = 'entityClass' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $entityClassName . ' implements \TYPO3\FLOW3\Object\Proxy\ProxyInterface {
			public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
		$mockEntity = $this->getMock($entityClassName, array(), array(), '', FALSE);
		$mockParty = $this->getMock('TYPO3\Party\Domain\Model\AbstractParty', array(), array(), '', FALSE);

		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('isNewObject')->with($mockParty)->will($this->returnValue(FALSE));
		$mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->with($mockParty)->will($this->returnValue('uuid'));

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isClassAnnotatedWith')->with($mockParty, 'TYPO3\FLOW3\Annotations\Entity')->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('getValueForOperand')->with('current.party')->will($this->returnValue($mockParty));
		$rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue($mockParty));
		$rewritingAspect->expects($this->at(2))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue($mockParty));
		$rewritingAspect->expects($this->at(3))->method('getValueForOperand')->with('current.party')->will($this->returnValue($mockParty));

		$rewritingAspect->_set('reflectionService', $mockReflectionService);
		$rewritingAspect->_set('persistenceManager', $mockPersistenceManager);

		$constraint1 = array(
			'operator' => '==',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$constraint2 = array(
			'operator' => '==',
			'leftValue' => 'this.party',
			'rightValue' =>  'current.party'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, $mockEntity));
		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, $mockEntity));
	}
}
?>
