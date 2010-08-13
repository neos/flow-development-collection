<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

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
 * Testcase for the persistence query rewriting aspect
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PersistenceQueryRewritingAspectTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewriteQomQueryAddsTheConstraintsGivenByThePolicyServiceCorrectlyToTheQueryObject() {
		$entityType = 'MyClass';

		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		$mockQuery->expects($this->once())->method('getConstraint')->will($this->returnValue('existingConstraint'));
		$mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));
		$mockQuery->expects($this->once())->method('logicalNot')->with('newConstraints')->will($this->returnValue('newConstraintsNegated'));
		$mockQuery->expects($this->once())->method('logicalAnd')->with('existingConstraint', 'newConstraintsNegated')->will($this->returnValue('mergedResultConstraints'));
		$mockQuery->expects($this->once())->method('matching')->with('mergedResultConstraints');

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('query')->will($this->returnValue($mockQuery));

		$roles = array('role1', 'role2');

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($roles));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Context')->will($this->returnValue($mockSecurityContext));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityType, $roles)->will($this->returnValue(array('parsedConstraints')));
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForConstraintDefinitions'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getQomConstraintForConstraintDefinitions')->with(array('parsedConstraints'), $mockQuery)->will($this->returnValue('newConstraints'));
		$rewritingAspect->injectPolicyService($mockPolicyService);
		$rewritingAspect->injectObjectManager($mockObjectManager);

		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewriteQomQueryFetchesTheSecurityContextOnTheFirstCallToBeSureTheSessionHasAlreadyBeenInitializedWhenTheContextIsBuilt() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('query')->will($this->returnValue($mockQuery));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Context')->will($this->returnValue($mockSecurityContext));
		$mockObjectManager->expects($this->once())->method('isSessionInitialized')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->will($this->returnValue(FALSE));

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->injectPolicyService($mockPolicyService);
		$rewritingAspect->injectObjectManager($mockObjectManager);

		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function anQomQueryIsNotRewrittenIfThereIsNoPolicyEntryForItsEntityType() {
		$entityType = 'MyClass';

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Context')->will($this->returnValue($mockSecurityContext));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(FALSE));

		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		$mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('query')->will($this->returnValue($mockQuery));

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
        $rewritingAspect->injectObjectManager($mockObjectManager);
		$rewritingAspect->injectPolicyService($mockPolicyService);
		$rewritingAspect->rewriteQomQuery($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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

		$expectedResult = array();

		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		$mockQuery->expects($this->at(0))->method('logicalAnd')->with('firstConstraintResult', 'thirdConstraintResult')->will($this->returnValue('firstAndThird'));
		$mockQuery->expects($this->at(1))->method('logicalAnd')->with('firstAndThird', 'fourthConstraintResult')->will($this->returnValue('firstAndThirdAndFourth'));
		$mockQuery->expects($this->at(2))->method('logicalOr')->with('firstAndThirdAndFourth', 'secondConstraintResult')->will($this->returnValue('firstAndThirdAndFourthOrSecond'));
		$mockQuery->expects($this->at(3))->method('logicalNot')->with('fifthConstraintResult')->will($this->returnValue('notFifth'));
		$mockQuery->expects($this->at(4))->method('logicalAnd')->with('firstAndThirdAndFourthOrSecond', 'notFifth')->will($this->returnValue('firstAndThirdAndFourthOrSecondAndNotFifth'));
		$mockQuery->expects($this->at(5))->method('logicalNot')->with('sixthConstraintResult')->will($this->returnValue('notSixth'));
		$mockQuery->expects($this->at(6))->method('logicalOr')->with('firstAndThirdAndFourthOrSecondAndNotFifth', 'notSixth')->will($this->returnValue('firstAndThirdAndFourthOrSecondAndNotFifthOrNotSixth'));

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForSingleConstraintDefinition'), array(), '', FALSE);
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	public function getQomConstraintForSingleConstraintDefinitionThrowsAnExceptionIfAConstraintHasNoReferenceToTheCurrentObjectIndicatedByTheThisKeyword() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);

		$constraint = array(
			'operator' => '==',
			'leftValue' => 'current.party',
			'rightValue' =>  'current.some.other.object'
		);

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getQomConstraintFoSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForAnEqualityOperatorComparingASimpleValue() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		$mockQuery->expects($this->once())->method('equals')->with('party', 'Andi')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => '==',
			'leftValue' => '"Andi"',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForGlobalObject'), array(), '', FALSE);
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForAnEqualityOperatorAccessingAGlobalObject() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		$mockQuery->expects($this->once())->method('equals')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => '==',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheInOperator() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		$mockQuery->expects($this->once())->method('in')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => 'in',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheContainsOperator() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		$mockQuery->expects($this->once())->method('contains')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

		$constraint = array(
			'operator' => 'contains',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('resultQomConstraint', $resultConstraint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheMatchesOperator() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\Query', array(), array(), '', FALSE);
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

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', FALSE);
		$rewritingAspect->expects($this->once())->method('getValueForOperand')->with(array(1, '"two"', 3))->will($this->returnValue(array(1, 'two', 3)));
		$resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

		$this->assertEquals('compositeConstraint2', $resultConstraint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValueForOperandReturnsTheCorrectValueForSimpleValues() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValueForOperandReturnsTheCorrectValueFromGlobalObjects() {
		$className = uniqid('dummyParty');
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

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->injectSettings($settings);

		$operand = 'current.party.name';

		$result = $rewritingAspect->_call('getValueForOperand', $operand);

		$this->assertEquals($result, 'Andi', 'The wrong value has been returned!');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValueForOperandReturnsTheCorrectValueForArrayOperands() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$operand = array(1, '"Andi"', 3, '\'Andi\'');

		$result = $rewritingAspect->_call('getValueForOperand', $operand);

		$expectedResult = array(1, 'Andi', 3, 'Andi');

		$this->assertEquals($result, $expectedResult, 'The wrong value has been returned!');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifierChecksTheConstraintsGivenByThePolicyServiceForTheReturnedObjectArray() {
		$queryResult = array(
			'identifier' => '123',
			'classname' => 'MyClass',
			'properties' => array()
		);

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue($queryResult));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$roles = array('role1', 'role2');

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue($roles));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Context')->will($this->returnValue($mockSecurityContext));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getResourcesConstraintsForEntityTypeAndRoles')->with('MyClass', $roles)->will($this->returnValue(array('parsedConstraints')));
		$mockPolicyService->expects($this->any())->method('hasPolicyEntryForEntityType')->with('MyClass')->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('checkConstraintDefinitionsOnResultArray'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('checkConstraintDefinitionsOnResultArray')->with(array('parsedConstraints'), $queryResult)->will($this->returnValue(TRUE));
		$rewritingAspect->expects($this->at(1))->method('checkConstraintDefinitionsOnResultArray')->with(array('parsedConstraints'), $queryResult)->will($this->returnValue(FALSE));
		$rewritingAspect->injectPolicyService($mockPolicyService);
		$rewritingAspect->injectObjectManager($mockObjectManager);

		$this->assertEquals($queryResult, $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint));
		$this->assertEquals(array(), $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifierFetchesTheSecurityContextOnTheFirstCallToBeSureTheSessionHasAlreadyBeenInitializedWhenTheContextIsBuilt() {
		$queryResult = array(
			'identifier' => '123',
			'classname' => 'MyClass',
			'properties' => array()
		);

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue($queryResult));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Context')->will($this->returnValue($mockSecurityContext));
		$mockObjectManager->expects($this->once())->method('isSessionInitialized')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->will($this->returnValue(FALSE));

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);
		$rewritingAspect->injectPolicyService($mockPolicyService);
		$rewritingAspect->injectObjectManager($mockObjectManager);

		$rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkConstraintDefinitionsOnResultArrayBasicallyWorks() {
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

		$expectedResult = array();

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('checkSingleConstraintDefinitionOnResultArray'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('checkSingleConstraintDefinitionOnResultArray')->with(array('firstConstraint'), array())->will($this->returnValue(FALSE));
		$rewritingAspect->expects($this->at(1))->method('checkSingleConstraintDefinitionOnResultArray')->with(array('thirdConstraint'), array())->will($this->returnValue(FALSE));
		$rewritingAspect->expects($this->at(2))->method('checkSingleConstraintDefinitionOnResultArray')->with(array('fourthConstraint'), array())->will($this->returnValue(TRUE));
		$rewritingAspect->expects($this->at(3))->method('checkSingleConstraintDefinitionOnResultArray')->with(array('secondConstraint'), array())->will($this->returnValue(TRUE));
		$rewritingAspect->expects($this->at(4))->method('checkSingleConstraintDefinitionOnResultArray')->with(array('fifthConstraint'), array())->will($this->returnValue(FALSE));
		$rewritingAspect->expects($this->at(5))->method('checkSingleConstraintDefinitionOnResultArray')->with(array('sixthConstraint'), array())->will($this->returnValue(FALSE));

		$result = $rewritingAspect->_call('checkConstraintDefinitionsOnResultArray', $parsedConstraints, array());

		$this->assertTrue($result);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkSingleConstraintDefinitionOnResultArrayCallsGetResultValueForObjectAccessStringForAllExpressionsStartingWithThis() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getResultValueForObjectAccessExpression'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(1))->method('getResultValueForObjectAccessExpression')->with('accounts.title', array());
		$rewritingAspect->expects($this->at(2))->method('getResultValueForObjectAccessExpression')->with('accounts.title', array());
		$rewritingAspect->expects($this->at(4))->method('getResultValueForObjectAccessExpression')->with('accounts.title', array());
		$rewritingAspect->expects($this->at(5))->method('getResultValueForObjectAccessExpression')->with('party.name', array());

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

		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint1, array());
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint2, array());
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint3, array());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkSingleConstraintDefinitionOnResultArrayCallsGetValueForOperandForAllExpressionsNotStartingWithThis() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getResultValueForObjectAccessExpression'), array(), '', FALSE);
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

		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint1, array());
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint2, array());
		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint3, array());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	public function checkSingleConstraintDefinitionOnResultArrayThrowsAnExceptionIfAConstraintHasNoReferenceToTheCurrentObjectIndicatedByTheThisKeyword() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$constraint = array(
			'operator' => '==',
			'leftValue' => '"blub"',
			'rightValue' =>  'NULL'
		);

		$rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint, array());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkSingleConstraintDefinitionOnResultArrayWorksForEqualityOperators() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getResultValueForObjectAccessExpression'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('"blub"')->will($this->returnValue('blub'));
		$rewritingAspect->expects($this->any())->method('getResultValueForObjectAccessExpression')->with('accounts.title')->will($this->returnValue('blub'));

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

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint1, array()));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint2, array()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkSingleConstraintDefinitionOnResultArrayWorksForTheInOperator() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getResultValueForObjectAccessExpression'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue('blub'));
		$rewritingAspect->expects($this->at(1))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue(array('bla', 'blub', 'foo')));
		$rewritingAspect->expects($this->at(3))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue(array('bla', 'foo', 'bar')));

		$constraint = array(
			'operator' => 'in',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint, array()));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint, array()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkSingleConstraintDefinitionOnResultArrayWorksForTheContainsOperator() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getResultValueForObjectAccessExpression'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue(array('bla', 'blub', 'foo')));
		$rewritingAspect->expects($this->at(1))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue('blub'));
		$rewritingAspect->expects($this->at(3))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue('bar'));

		$constraint = array(
			'operator' => 'contains',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint, array()));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint, array()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkSingleConstraintDefinitionOnResultArrayWorksForTheMatchesOperator() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getResultValueForObjectAccessExpression'), array(), '', FALSE);
		$rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue(array('bla', 'blub', 'blubber')));
		$rewritingAspect->expects($this->at(1))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue(array('hinz', 'blub', 'kunz')));
		$rewritingAspect->expects($this->at(3))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue(array('foo', 'bar', 'baz')));

		$constraint = array(
			'operator' => 'matches',
			'leftValue' => 'current.party',
			'rightValue' =>  'this.party'
		);

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint, array()));
		$this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint, array()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkSingleConstraintDefinitionOnResultArrayComparesTheIdentifierWhenComparingPersitedObjects() {
		$entityClassName = uniqid('entityClass');
		eval('class ' . $entityClassName . ' implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface, \F3\FLOW3\AOP\ProxyInterface {
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return get_class($this); }
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) {}
			public function FLOW3_AOP_Proxy_getProperty($propertyName) {}
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue) {}
			public function FLOW3_Persistence_isNew() {}
			public function FLOW3_Persistence_isClone() {}
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function __clone() {}
		}');
		$mockEntity = $this->getMock($entityClassName, array(), array(), '', FALSE);
		
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('isNewObject')->with($mockEntity)->will($this->returnValue(FALSE));
		$mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->with($mockEntity)->will($this->returnValue('uuid'));
		
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isClassTaggedWith')->with($mockEntity, 'entity')->will($this->returnValue(TRUE));

		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getResultValueForObjectAccessExpression'), array(), '', FALSE);
		$rewritingAspect->expects($this->at(0))->method('getValueForOperand')->with('current.party')->will($this->returnValue($mockEntity));
		$rewritingAspect->expects($this->at(1))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue('uuid'));
		$rewritingAspect->expects($this->at(2))->method('getResultValueForObjectAccessExpression')->with('party')->will($this->returnValue('uuid'));
		$rewritingAspect->expects($this->at(3))->method('getValueForOperand')->with('current.party')->will($this->returnValue($mockEntity));

		$rewritingAspect->injectReflectionService($mockReflectionService);
		$rewritingAspect->injectPersistenceManager($mockPersistenceManager);

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

		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint1, array()));
		$this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultArray', $constraint2, array()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getResultValueForObjectAccessExpressionReturnsTheCorrectCodeForANestedObjectAccess() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$queryResult = array(
			'type' => 'F3\MyObject',
			'properties' => array(
				'first' => array(
					'type' => 'F3\MyObject',
					'value' => array(
						'properties' => array(
							'second' => array(
								'type' => 'F3\MyObject',
								'value' => array(
									'properties' => array(
										'third' => array(
											'type' => 'string',
											'value' => 'TestValue'
										)
									)
								)
							)
						)
					)
				)
			)
		);

		$expression = 'first.second.third';

		$result = $rewritingAspect->_call('getResultValueForObjectAccessExpression', $expression, $queryResult);

		$this->assertEquals('TestValue', $result, 'The wrong value has been returned!');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getResultValueForObjectAccessExpressionReturnsTheUUIDOfTheObjectForANestedObjectAccessPointNotToASimpleProperty() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$queryResult = array(
			'type' => 'F3\MyObject',
			'properties' => array(
				'first' => array(
					'type' => 'F3\MyObject',
					'value' => array(
						'properties' => array(
							'second' => array(
								'type' => 'F3\MyObject',
								'value' => array(
									'identifier' => 'some-uuid',
									'properties' => array(
										'third' => array(
											'type' => 'string',
											'value' => 'TestValue'
										)
									)
								)
							)
						)
					)
				)
			)
		);

		$expression = 'first.second';

		$result = $rewritingAspect->_call('getResultValueForObjectAccessExpression', $expression, $queryResult);

		$this->assertEquals('some-uuid', $result, 'The uuid of the object has not been returned as expected!');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	public function getResultValueForObjectAccessExpressionThrowsAnExceptionIfTheGivenObejctPathDoesNotMatchTheReturnedObjectStructure() {
		$rewritingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', FALSE);

		$queryResult = array(
			'classname' => 'F3\MyObject',
			'properties' => array(
				'first' => array(
					'type' => 'F3\MyObject',
					'value' => array(
						'properties' => array(
							'second' => array(
								'type' => 'string',
								'value' => 'TestValue'
							)
						)
					)
				)
			)
		);

		$expression = 'first.second.third';

		$result = $rewritingAspect->_call('getResultValueForObjectAccessExpression', $expression, $queryResult);
	}
}
?>
