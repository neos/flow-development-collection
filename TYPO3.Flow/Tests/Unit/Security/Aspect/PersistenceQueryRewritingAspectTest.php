<?php
namespace TYPO3\Flow\Tests\Unit\Security\Aspect;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the persistence query rewriting aspect
 *
 * @covers TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect
 */
class PersistenceQueryRewritingAspectTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function rewriteQomQueryAddsTheConstraintsGivenByThePolicyServiceCorrectlyToTheQueryObject()
    {
        $entityType = 'MyClass';

        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->atLeastOnce())->method('getConstraint')->will($this->returnValue('existingConstraint'));
        $mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));
        $mockQuery->expects($this->once())->method('logicalAnd')->with('existingConstraint', 'newConstraints')->will($this->returnValue('mergedResultConstraints'));
        $mockQuery->expects($this->once())->method('matching')->with('mergedResultConstraints');

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed');

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

        $roles = array('role1', 'role2');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($roles));
        $mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityType, $roles)->will($this->returnValue(array('parsedConstraints')));
        $mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(true));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForConstraintDefinitions'), array(), '', false);
        $rewritingAspect->expects($this->once())->method('getQomConstraintForConstraintDefinitions')->with(array('parsedConstraints'), $mockQuery)->will($this->returnValue('newConstraints'));
        $rewritingAspect->_set('policyService', $mockPolicyService);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('alreadyRewrittenQueries', new \SplObjectStorage());

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     */
    public function rewriteQomQueryUsesTheConstraintsGivenByThePolicyServiceInTheQueryObject()
    {
        $entityType = 'MyClass';

        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->atLeastOnce())->method('getConstraint')->will($this->returnValue(null));
        $mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));
        $mockQuery->expects($this->never())->method('logicalAnd');
        $mockQuery->expects($this->once())->method('matching')->with('newConstraints');

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed');

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

        $roles = array('role1', 'role2');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($roles));
        $mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityType, $roles)->will($this->returnValue(array('parsedConstraints')));
        $mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(true));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForConstraintDefinitions'), array(), '', false);
        $rewritingAspect->expects($this->once())->method('getQomConstraintForConstraintDefinitions')->with(array('parsedConstraints'), $mockQuery)->will($this->returnValue('newConstraints'));
        $rewritingAspect->_set('policyService', $mockPolicyService);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('alreadyRewrittenQueries', new \SplObjectStorage());

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     */
    public function rewriteQomQueryDoesNotChangeTheOriginalQueryConstraintsIfThereIsAPolicyEntryButNoAdditionalConstraintsAreNeededInTheCurrentSituation()
    {
        $entityType = 'MyClass';

        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));
        $mockQuery->expects($this->never())->method('matching');

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed');

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

        $roles = array('role1', 'role2');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($roles));
        $mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityType, $roles)->will($this->returnValue(array('parsedConstraints')));
        $mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(true));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getQomConstraintForConstraintDefinitions'), array(), '', false);
        $rewritingAspect->expects($this->once())->method('getQomConstraintForConstraintDefinitions')->with(array('parsedConstraints'), $mockQuery)->will($this->returnValue(null));
        $rewritingAspect->_set('policyService', $mockPolicyService);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('alreadyRewrittenQueries', new \SplObjectStorage());

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     */
    public function rewriteQomQueryInitializesSecurityContextIfPossible()
    {
        $entityType = 'MyClass';

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed');

        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->once())->method('initialize');
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(false));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('alreadyRewrittenQueries', new \SplObjectStorage());
        $rewritingAspect->_set('policyService', $mockPolicyService);

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     */
    public function rewriteQomQueryDoesNotRewriteQueryIfAuthorizationIsDisabled()
    {
        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->never())->method('getProxy');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->never())->method('isInitialized');

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('policyService', $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false));

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     */
    public function rewriteQomQueryDoesNotRewriteQueryIfPolicyHasNoEntriesForEntities()
    {
        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->never())->method('getProxy');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('areAuthorizationChecksDisabled')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->never())->method('isInitialized');

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('hasPolicyEntriesForEntities')->will($this->returnValue(false));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('policyService', $mockPolicyService);

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     */
    public function rewriteQomQueryDoesNotRewriteQueryIfSecurityContextCannotBeInitialized()
    {
        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed');

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->never())->method('getProxy');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(false));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('alreadyRewrittenQueries', new \SplObjectStorage());
        $rewritingAspect->_set('policyService', $this->getMock('TYPO3\Flow\Security\Policy\PolicyService'));

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     */
    public function aQomQueryIsNotRewrittenIfThereIsNoPolicyEntryForItsEntityType()
    {
        $entityType = 'MyClass';

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->with($entityType)->will($this->returnValue(false));

        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->once())->method('getType')->will($this->returnValue($entityType));

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed');

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockQuery));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('policyService', $mockPolicyService);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('alreadyRewrittenQueries', new \SplObjectStorage());

        $rewritingAspect->rewriteQomQuery($mockJoinPoint);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException
     */
    public function getQomConstraintForSingleConstraintDefinitionThrowsAnExceptionIfAConstraintHasNoReferenceToTheCurrentObjectIndicatedByTheThisKeyword()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');

        $constraint = array(
            'operator' => '==',
            'leftValue' => 'current.party',
            'rightValue' =>  'current.some.other.object'
        );

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);
    }

    /**
     * @test
     */
    public function getQomConstraintFoSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForAnEqualityOperatorComparingASimpleValue()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->once())->method('equals')->with('party', 'Andi')->will($this->returnValue('resultQomConstraint'));

        $constraint = array(
            'operator' => '==',
            'leftValue' => '"Andi"',
            'rightValue' =>  'this.party'
        );

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForGlobalObject'), array(), '', false);
        $resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

        $this->assertEquals('resultQomConstraint', $resultConstraint);
    }

    /**
     * @test
     */
    public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForAnEqualityOperatorAccessingAGlobalObject()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->once())->method('equals')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

        $constraint = array(
            'operator' => '==',
            'leftValue' => 'current.party',
            'rightValue' =>  'this.party'
        );

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', false);
        $rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
        $resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

        $this->assertEquals('resultQomConstraint', $resultConstraint);
    }

    /**
     * @test
     */
    public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheInOperator()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->once())->method('in')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

        $constraint = array(
            'operator' => 'in',
            'leftValue' => 'current.party',
            'rightValue' =>  'this.party'
        );

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', false);
        $rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
        $resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

        $this->assertEquals('resultQomConstraint', $resultConstraint);
    }

    /**
     * @test
     */
    public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheContainsOperator()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->once())->method('contains')->with('party', 'globalParty')->will($this->returnValue('resultQomConstraint'));

        $constraint = array(
            'operator' => 'contains',
            'leftValue' => 'current.party',
            'rightValue' =>  'this.party'
        );

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', false);
        $rewritingAspect->expects($this->once())->method('getValueForOperand')->with('current.party')->will($this->returnValue('globalParty'));
        $resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

        $this->assertEquals('resultQomConstraint', $resultConstraint);
    }

    /**
     * @test
     */
    public function getQomConstraintForSingleConstraintDefinitionBuildsTheCorrectConstraintObjectForTheMatchesOperator()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
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

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand'), array(), '', false);
        $rewritingAspect->expects($this->once())->method('getValueForOperand')->with(array(1, '"two"', 3))->will($this->returnValue(array(1, 'two', 3)));
        $resultConstraint = $rewritingAspect->_call('getQomConstraintForSingleConstraintDefinition', $constraint, $mockQuery);

        $this->assertEquals('compositeConstraint2', $resultConstraint);
    }

    /**
     * @test
     */
    public function getValueForOperandReturnsTheCorrectValueForSimpleValues()
    {
        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);

        $operandValues = array(
            '"Andi"' => 'Andi',
            '\'Andi\'' => 'Andi',
            1 => 1,
            'TRUE' => true,
            'FALSE' => false,
            'NULL' => null
        );

        foreach ($operandValues as $operand => $expectedResult) {
            $result = $rewritingAspect->_call('getValueForOperand', $operand);
            $this->assertEquals($result, $expectedResult, 'The wrong value has been returned!');
        }
    }

    /**
     * @test
     */
    public function getValueForOperandReturnsTheCorrectValueFromGlobalObjects()
    {
        $className = 'dummyParty' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . ' {
				protected $name = "Andi";
				public function getName() {
					return $this->name;
				}
			}
		');

        $globalObject = new $className();

        $settings = array(
            'aop' => array(
                'globalObjects' => array(
                    'party' => $className
                )
            )
        );

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('get')->with($className)->will($this->returnValue($globalObject));

        $mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface', array(), array(), '', false);
        $mockPersistenceManager->expects($this->any())->method('isNewObject')->will($this->returnValue(false));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('persistenceManager', $mockPersistenceManager);
        $rewritingAspect->_set('objectManager', $mockObjectManager);
        $rewritingAspect->injectSettings($settings);

        $operand = 'current.party.name';

        $result = $rewritingAspect->_call('getValueForOperand', $operand);

        $this->assertEquals($result, 'Andi', 'The wrong value has been returned!');
    }

    /**
     * @test
     */
    public function getValueForOperandReturnsTheCorrectValueForArrayOperands()
    {
        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);

        $operand = array(1, '"Andi"', 3, '\'Andi\'');

        $result = $rewritingAspect->_call('getValueForOperand', $operand);

        $expectedResult = array(1, 'Andi', 3, 'Andi');

        $this->assertEquals($result, $expectedResult, 'The wrong value has been returned!');
    }

    /**
     * @test
     */
    public function checkAccessAfterFetchingAnObjectByIdentifierChecksTheConstraintsGivenByThePolicyServiceForTheReturnedObject()
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $result = new $entityClassName();

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue($result));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $roles = array('role1', 'role2');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue($roles));
        $mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->any())->method('getResourcesConstraintsForEntityTypeAndRoles')->with($entityClassName, $roles)->will($this->returnValue(array('parsedConstraints')));
        $mockPolicyService->expects($this->any())->method('hasPolicyEntryForEntityType')->with($entityClassName)->will($this->returnValue(true));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('checkConstraintDefinitionsOnResultObject'), array(), '', false);
        $rewritingAspect->expects($this->at(0))->method('checkConstraintDefinitionsOnResultObject')->with(array('parsedConstraints'), $result)->will($this->returnValue(true));
        $rewritingAspect->expects($this->at(1))->method('checkConstraintDefinitionsOnResultObject')->with(array('parsedConstraints'), $result)->will($this->returnValue(false));
        $rewritingAspect->_set('policyService', $mockPolicyService);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('reflectionService', $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array('initialize')));

        $this->assertEquals($result, $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint));
        $this->assertEquals(null, $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint));
    }

    /**
     * @test
     */
    public function checkAccessAfterFetchingAnObjectByIdentifierFetchesTheSecurityContextOnTheFirstCallToBeSureTheSessionHasAlreadyBeenInitializedWhenTheContextIsBuilt()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->any())->method('getType')->will($this->returnValue('MyClass'));

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(null));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array()));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('hasPolicyEntryForEntityType')->will($this->returnValue(false));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('policyService', $mockPolicyService);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('reflectionService', $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array('initialize')));

        $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
    }

    /**
     * @test
     */
    public function checkAccessAfterFetchingAnObjectByIdentifierInitializesSecurityContextIfPossible()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->any())->method('getType')->will($this->returnValue('MyClass'));

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(null));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->once())->method('initialize');
        $mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array()));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('policyService', $this->getMock('TYPO3\Flow\Security\Policy\PolicyService'));
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('reflectionService', $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array('initialize')));

        $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
    }


    /**
     * @test
     */
    public function checkAccessAfterFetchingAnObjectByIdentifierReturnsObjectIfAuthorizationIsDisabled()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->any())->method('getType')->will($this->returnValue('MyClass'));

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(null));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->never())->method('isInitialized');

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('policyService', $this->getMock('TYPO3\Flow\Security\Policy\PolicyService'));

        $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
    }

    /**
     * @test
     */
    public function checkAccessAfterFetchingAnObjectByIdentifierReturnsObjectIfPolicyHasNoEntriesForEntities()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->any())->method('getType')->will($this->returnValue('MyClass'));

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(null));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->once())->method('hasPolicyEntriesForEntities')->will($this->returnValue(false));
        $mockPolicyService->expects($this->never())->method('hasPolicyEntryForEntityType');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->never())->method('isInitialized');

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('policyService', $mockPolicyService);

        $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
    }

    /**
     * @test
     */
    public function checkAccessAfterFetchingAnObjectByIdentifierReturnsObjectIfSecurityContextCannotInitialized()
    {
        $mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
        $mockQuery->expects($this->any())->method('getType')->will($this->returnValue('MyClass'));

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(null));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService', array(), array(), '', false);
        $mockPolicyService->expects($this->never())->method('hasPolicyEntryForEntityType');

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('isInitialized')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(false));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);
        $rewritingAspect->_set('securityContext', $mockSecurityContext);
        $rewritingAspect->_set('policyService', $mockPolicyService);
        $rewritingAspect->_set('reflectionService', $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array('dummy')));

        $rewritingAspect->checkAccessAfterFetchingAnObjectByIdentifier($mockJoinPoint);
    }

    /**
     * @test
     */
    public function checkConstraintDefinitionsOnResultObjectBasicallyWorks()
    {
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

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('checkSingleConstraintDefinitionOnResultObject'), array(), '', false);
        $rewritingAspect->expects($this->at(0))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('firstConstraint'), array())->will($this->returnValue(false));
        $rewritingAspect->expects($this->at(1))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('thirdConstraint'), array())->will($this->returnValue(false));
        $rewritingAspect->expects($this->at(2))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('fourthConstraint'), array())->will($this->returnValue(true));
        $rewritingAspect->expects($this->at(3))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('secondConstraint'), array())->will($this->returnValue(true));
        $rewritingAspect->expects($this->at(4))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('fifthConstraint'), array())->will($this->returnValue(false));
        $rewritingAspect->expects($this->at(5))->method('checkSingleConstraintDefinitionOnResultObject')->with(array('sixthConstraint'), array())->will($this->returnValue(false));

        $this->assertFalse($rewritingAspect->_call('checkConstraintDefinitionsOnResultObject', $parsedConstraints, array()));
    }

    /**
     * @test
     */
    public function checkSingleConstraintDefinitionOnResultObjectCallsGetObjectValueByPathForAllExpressionsStartingWithThis()
    {
        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);
        $rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with(null, 'accounts.title');
        $rewritingAspect->expects($this->at(2))->method('getObjectValueByPath')->with(null, 'accounts.title');
        $rewritingAspect->expects($this->at(4))->method('getObjectValueByPath')->with(null, 'accounts.title');
        $rewritingAspect->expects($this->at(5))->method('getObjectValueByPath')->with(null, 'party.name');

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

        $rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, null);
        $rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, null);
        $rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint3, null);
    }

    /**
     * @test
     */
    public function checkSingleConstraintDefinitionOnResultObjectCallsGetValueForOperandForAllExpressionsNotStartingWithThis()
    {
        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);
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

        $rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, null);
        $rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, null);
        $rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint3, null);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException
     */
    public function checkSingleConstraintDefinitionOnResultObjectThrowsAnExceptionIfAConstraintHasNoReferenceToTheCurrentObjectIndicatedByTheThisKeyword()
    {
        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('dummy'), array(), '', false);

        $constraint = array(
            'operator' => '==',
            'leftValue' => '"blub"',
            'rightValue' =>  'NULL'
        );

        $rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, null);
    }

    /**
     * @test
     */
    public function checkSingleConstraintDefinitionOnResultObjectWorksForEqualityOperators()
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $mockEntity = $this->getMock($entityClassName, array(), array(), '', false);

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);
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

        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, $mockEntity));
        $this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, $mockEntity));
    }

    /**
     * @test
     */
    public function checkSingleConstraintDefinitionOnResultObjectWorksForTheInOperator()
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $mockEntity = $this->getMock($entityClassName, array(), array(), '', false);

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);
        $rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue('blub'));
        $rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('bla', 'blub', 'foo')));
        $rewritingAspect->expects($this->at(3))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('bla', 'foo', 'bar')));

        $constraint = array(
            'operator' => 'in',
            'leftValue' => 'current.party',
            'rightValue' =>  'this.party'
        );

        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
        $this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
    }

    /**
     * @test
     */
    public function checkSingleConstraintDefinitionOnResultObjectWorksForTheContainsOperator()
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $mockEntity = $this->getMock($entityClassName, array(), array(), '', false);

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);
        $rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue(array('bla', 'blub', 'foo')));
        $rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue('blub'));
        $rewritingAspect->expects($this->at(3))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue('bar'));

        $constraint = array(
            'operator' => 'contains',
            'leftValue' => 'current.party',
            'rightValue' =>  'this.party'
        );

        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
        $this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
    }

    /**
     * @test
     */
    public function checkSingleConstraintDefinitionOnResultObjectWorksForTheMatchesOperator()
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $mockEntity = $this->getMock($entityClassName, array(), array(), '', false);

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);
        $rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue(array('bla', 'blub', 'blubber')));
        $rewritingAspect->expects($this->at(1))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('hinz', 'blub', 'kunz')));
        $rewritingAspect->expects($this->at(3))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue(array('foo', 'bar', 'baz')));

        $constraint = array(
            'operator' => 'matches',
            'leftValue' => 'current.party',
            'rightValue' =>  'this.party'
        );

        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
        $this->assertFalse($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
    }

    /**
     * @test
     */
    public function checkSingleConstraintDefinitionOnResultObjectComparesTheIdentifierWhenComparingPersistedObjects()
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $mockEntity = $this->getMock($entityClassName, array(), array(), '', false);
        $mockParty = $this->getMock('TYPO3\Party\Domain\Model\AbstractParty', array(), array(), '', false);

        $mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface', array(), array(), '', false);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->with($mockParty)->will($this->returnValue('uuid'));

        $mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array(), array(), '', false);
        $mockReflectionService->expects($this->any())->method('getClassNameByObject')->with($mockParty)->will($this->returnValue(get_class($mockParty)));
        $mockReflectionService->expects($this->any())->method('isClassAnnotatedWith')->with(get_class($mockParty), 'TYPO3\Flow\Annotations\Entity')->will($this->returnValue(true));

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);
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

        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint1, $mockEntity));
        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint2, $mockEntity));
    }

    /**
     * signature: $operator, $leftValue, $rightValue, $expectationGetObjectValueByPathAt
     */
    public function checkSingleConstraintDefinitionOnResultObjectCanWorkWithCollectionsDataProvider()
    {
        return array(
            array('in', 'this.party', 'current.party', 0),
            array('contains', 'current.party', 'this.party', 1)
        );
    }

    /**
     * @test
     * @dataProvider checkSingleConstraintDefinitionOnResultObjectCanWorkWithCollectionsDataProvider
     */
    public function checkSingleConstraintDefinitionOnResultObjectCanWorkWithCollections($operator, $leftValue, $rightValue, $expectationGetObjectValueByPathAt)
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $mockEntity = $this->getMock($entityClassName, array(), array(), '', false);

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);

        $mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array(), array(), '', false);
        $mockReflectionService->expects($this->any())->method('getClassNameByObject');
        $mockReflectionService->expects($this->any())->method('isClassAnnotatedWith')->will($this->returnValue(false));
        $rewritingAspect->_set('reflectionService', $mockReflectionService);

        $mockCollection = $this->getMock('Doctrine\Common\Collections\Collection');
        $mockCollection->expects($this->atLeastOnce())->method('contains')->with('blub')->will($this->returnValue(true));
        $rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue($mockCollection));
        $rewritingAspect->expects($this->at($expectationGetObjectValueByPathAt))->method('getObjectValueByPath')->with($mockEntity, 'party')->will($this->returnValue('blub'));

        $constraint = array(
            'operator' => $operator,
            'leftValue' => $leftValue,
            'rightValue' =>  $rightValue
        );

        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
    }

    /**
     * signature: $leftValue, $rightValue
     */
    public function checkSingleConstraintDefinitionOnResultObjectCanWorkWithCollectionsOnMatchesOperatorDataProvider()
    {
        return array(
            array('this.party', 'current.party'),
            array('current.party', 'this.party'),
            array('this.party', 'this.collection'),
        );
    }

    /**
     * @test
     * @dataProvider checkSingleConstraintDefinitionOnResultObjectCanWorkWithCollectionsOnMatchesOperatorDataProvider
     */
    public function checkSingleConstraintDefinitionOnResultObjectCanWorkWithCollectionsOnMatchesOperator($leftValue, $rightValue)
    {
        $entityClassName = 'entityClass' . md5(uniqid(mt_rand(), true));
        eval('class ' . $entityClassName . ' implements \TYPO3\Flow\Object\Proxy\ProxyInterface {
			public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}
			public function __clone() {}
			public function __wakeup() {}
		}');
        $mockEntity = $this->getMock($entityClassName, array(), array(), '', false);

        $rewritingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PersistenceQueryRewritingAspect', array('getValueForOperand', 'getObjectValueByPath'), array(), '', false);

        $mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array(), array(), '', false);
        $mockReflectionService->expects($this->any())->method('getClassNameByObject');
        $mockReflectionService->expects($this->any())->method('isClassAnnotatedWith')->will($this->returnValue(false));
        $rewritingAspect->_set('reflectionService', $mockReflectionService);

        $mockCollection = $this->getMock('Doctrine\Common\Collections\Collection');
        $mockCollection->expects($this->atLeastOnce())->method('toArray')->will($this->returnValue(array('foo')));
        $rewritingAspect->expects($this->any())->method('getValueForOperand')->with('current.party')->will($this->returnValue(array('foo')));
        $rewritingAspect->expects($this->any())->method('getObjectValueByPath')->will($this->returnValue($mockCollection));

        $constraint = array(
            'operator' => 'matches',
            'leftValue' => $leftValue,
            'rightValue' =>  $rightValue
        );

        $this->assertTrue($rewritingAspect->_call('checkSingleConstraintDefinitionOnResultObject', $constraint, $mockEntity));
    }
}
