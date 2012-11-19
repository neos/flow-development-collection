<?php
namespace TYPO3\Flow\Tests\Unit\Security\Policy;

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
 * Testcase for for the policy service
 *
 */
use TYPO3\Flow\Security\Policy\Role;

class PolicyServiceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function thePolicyIsLoadedCorrectlyFromTheConfigurationManager() {
		$mockPolicyExpressionParser = $this->getMock('TYPO3\Flow\Security\Policy\PolicyExpressionParser');

		$policy = array(
			'roles' => array('THE_ROLE' => array()),
			'resources' => array(
				'methods' => array('theResource' => 'method(Foo->bar())'),
				'entities' => array()
			),
			'acls' => array(
				'theRole' => array(
					'methods' => array(
						'theMethodResource' => 'GRANT'
					),
					'entities' => array(
						'theEntityResource' => 'GRANT'
					)
				)
			)
		);

		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('Flow_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = new \TYPO3\Flow\Security\Policy\PolicyService();
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);

		$policyService->initializeObject();
	}

	/**
	 * @test
	 */
	public function initializeObjectSetsTheEverybodyRoleInThePolicy() {
		$mockPolicyExpressionParser = $this->getMock('TYPO3\Flow\Security\Policy\PolicyExpressionParser');

		$policy = array(
			'roles' => array(),
			'resources' => array(
				'methods' => array(),
				'entities' => array()
			),
			'acls' => array()
		);

		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('Flow_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('parseEntityAcls'));
		$policyService->expects($this->once())->method('parseEntityAcls')->will($this->returnValue(array()));
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);

		$policyService->initializeObject();

		$expectedPolicy = array(
			'roles' => array('Everybody' => array()),
			'resources' => array(
				'methods' => array(),
				'entities' => array()
			),
			'acls' => array(
				'Everybody' => array(
					'methods' => array(),
					'entities' => array()
				)
			)
		);

		$this->assertEquals($expectedPolicy, $policyService->_get('policy'));
	}

	/**
	 * @test
	 */
	public function initializeObjectAddsTheAbstainPrivilegeForTheEverybodyRoleToEveryResourceWhereNoOtherPrivilegeIsSetInThePolicy() {
		$mockPolicyExpressionParser = $this->getMock('TYPO3\Flow\Security\Policy\PolicyExpressionParser');

		$policy = array(
			'roles' => array(),
			'resources' => array(
				'methods' => array(
					'methodResource1' => 'expression',
					'methodResource2' => 'expression',
				),
				'entities' => array(
					'class1' => array(
						'entityResource1' => 'expression'
					),
					'class2' => array(
						'entityResource2' => 'expression'
					)
				)
			),
			'acls' => array('Everybody' => array(
				'methods' => array(
					'methodResource2' => 'GRANT'
				),
				'entities' => array(
					'entityResource2' => 'DENY',
				)
			))
		);

		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('Flow_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('parseEntityAcls'));
		$policyService->expects($this->once())->method('parseEntityAcls')->will($this->returnValue(array()));
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);

		$policyService->initializeObject();

		$expectedPolicy = array(
			'roles' => array('Everybody' => array()),
			'resources' => array(
				'methods' => array(
					'methodResource1' => 'expression',
					'methodResource2' => 'expression',
				),
				'entities' => array(
					'class1' => array(
						'entityResource1' => 'expression'
					),
					'class2' => array(
						'entityResource2' => 'expression'
					)
				)
			),
			'acls' => array('Everybody' => array(
				'methods' => array(
					'methodResource2' => 'GRANT',
					'methodResource1' => 'ABSTAIN',
				),
				'entities' => array(
					'entityResource2' => 'DENY',
					'entityResource1' => 'ABSTAIN',
				)
			))
		);

		$this->assertEquals($expectedPolicy, $policyService->_get('policy'));
	}

	/**
	 * @test
	 */
	public function matchesAsksThePolicyExpressionParserToBuildPointcutFiltersForMethodResourcesAndChecksIfTheyMatchTheGivenClassAndMethod() {
		$settings = array(
			'security' => array(
				'enable' => TRUE
			)
		);

		$policy = array(
			'roles' => array('TheRole' => array()),
			'resources' => array(
				'methods' => array('theResource' => 'method(Foo->bar())'),
				'entities' => array()
			),
			'acls' => array('TheRole' => array('methods' => array('theResource' => 'GRANT')))
		);

		$mockFilter = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite');
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('TYPO3\Flow\Security\Policy\PolicyExpressionParser');
		$mockPolicyExpressionParser->expects($this->once())->method('parseMethodResources')->with('theResource', $policy['resources']['methods'])->will($this->returnValue($mockFilter));

		$accessibleProxyClassName = $this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService');
		$policyService = new $accessibleProxyClassName();
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);

		$this->assertTrue($policyService->matches('Foo', 'bar', 'Baz', 1));
	}

	/**
	 * @test
	 */
	public function matchesAddsRuntimeEvaluationsCorrectlyToTheInternalPolicyCache() {
		$settings = array(
			'security' => array(
				'enable' => TRUE
			)
		);

		$policy = array(
			'acls' => array('TheRole' => array(
				'methods' => array(
					'FirstResource' => 'GRANT',
					'SecondResource' => 'DENY',
					'ThirdResource' => 'DENY'
				)
			))
		);

		$mockFilter1 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite');
		$mockFilter1->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));
		$mockFilter1->expects($this->once())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));
		$mockFilter1->expects($this->once())->method('getRuntimeEvaluationsClosureCode')->will($this->returnValue('closureCode1'));

		$mockFilter2 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite');
		$mockFilter2->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));
		$mockFilter2->expects($this->once())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(FALSE));
		$mockFilter2->expects($this->never())->method('getRuntimeEvaluationsClosureCode');

		$mockFilter3 = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite');
		$mockFilter3->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));
		$mockFilter3->expects($this->once())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));
		$mockFilter3->expects($this->once())->method('getRuntimeEvaluationsClosureCode')->will($this->returnValue('closureCode3'));

		$filters = array(
			'TheRole' => array(
				'FirstResource' => $mockFilter1,
				'SecondResource' => $mockFilter2,
				'ThirdResource' => $mockFilter3
			)
		);

		$accessibleProxyClassName = $this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService');
		$policyService = new $accessibleProxyClassName();
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);
		$policyService->_set('filters', $filters);

		$policyService->matches('Foo', 'bar', 'Baz', 1);

		$expectedACLCache = array(
			'foo->bar' => array(
				'TheRole' => array(
					'FirstResource' => array(
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT,
						'runtimeEvaluationsClosureCode' => 'closureCode1'
					),
					'SecondResource' => array(
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY,
						'runtimeEvaluationsClosureCode' => FALSE
					),
					'ThirdResource' => array(
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY,
						'runtimeEvaluationsClosureCode' => 'closureCode3'
					)
				)
			)
		);

		$this->assertEquals($policyService->_get('acls'), $expectedACLCache);
	}

	/**
	 * @test
	 */
	public function matchesAlwaysReturnsFalseIfSecurityIsDisabled() {
		$settings = array('security' => array('enable' => FALSE));

		$policyService = new \TYPO3\Flow\Security\Policy\PolicyService();
		$policyService->injectSettings($settings);
		$this->assertFalse($policyService->matches('Foo', 'bar', 'Baz', 1));
	}

	/**
	 * @test
	 */
	public function matchesStoresMatchedPoliciesInAnArrayForLaterCaching() {
		$settings = array(
			'security' => array(
				'enable' => TRUE
				)
		);

		$policy = array(
			'roles' => array('theRole' => array()),
			'resources' => array(
				'methods' => array('theResource' => 'method(Foo->bar())'),
				'entities' => array()
			),
			'acls' => array('theRole' => array('methods' => array('theResource' => 'GRANT')))
		);

		$mockFilter = $this->getMock('TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite');
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('TYPO3\Flow\Security\Policy\PolicyExpressionParser');
		$mockPolicyExpressionParser->expects($this->once())->method('parseMethodResources')->with('theResource', $policy['resources']['methods'])->will($this->returnValue($mockFilter));

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService'), array('dummy'));
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);

		$policyService->matches('Foo', 'bar', 'Baz', 1);

		$expectedPolicies = array(
			'foo->bar' => array(
				'theRole' => array(
					'theResource' => array (
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT,
						'runtimeEvaluationsClosureCode' => FALSE
					)
				)
			)
		);

		$this->assertSame($expectedPolicies, $policyService->_get('acls'));
	}

	/**
	 * @test
	 */
	public function getPrivilegesForJoinPointReturnsAnEmptyArrayIfNoPrivilegesCouldBeFound() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService'), array('dummy'));
		$policyService->_set('acls', array('classname->methodname' => array()));

		$this->assertEquals(array(), $policyService->getPrivilegesForJoinPoint(new \TYPO3\Flow\Security\Policy\Role('Dummy'), $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function getPrivilegesForJoinPointReturnsThePrivilegesArrayThatHasBeenParsedForTheGivenJoinPointAndRole() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$role1 = new \TYPO3\Flow\Security\Policy\Role('role1');

		$privilegesArray = array(
			'FirstResource' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT,
			'SecondResource' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY,
			'ThirdResource' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
		);

		$aclsCache = array(
			'classname->methodname' => array(
				'role1' => array(
					'FirstResource' => array(
						'runtimeEvaluationsClosureCode' => FALSE,
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
					),
					'SecondResource' => array(
						'runtimeEvaluationsClosureCode' => FALSE,
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
					),
					'ThirdResource' => array(
						'runtimeEvaluationsClosureCode' => FALSE,
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
					)
				)
			)
		);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService'), array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForJoinPoint($role1, $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function getPrivilegesForJoinPointReturnsOnlyPrivilegesThatPassedRuntimeEvaluationsInThePrivilegesArrayThatHasBeenParsedForTheGivenJoinPointAndRole() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$role1 = new \TYPO3\Flow\Security\Policy\Role('role1');

		$privilegesArray = array('SecondResource' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT);

		$aclsCache = array(
			'classname->methodname' => array(
				'role1' => array(
					'FirstResource' => array(
						'runtimeEvaluationsClosureCode' => 'function () { return FALSE; };',
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
					),
					'SecondResource' => array(
						'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
						'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
					)
				)
			)
		);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService'), array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForJoinPoint($role1, $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsThePrivilegeThatHasBeenParsedForTheGivenResource() {
		$role1 = new \TYPO3\Flow\Security\Policy\Role('role1');

		$aclsCache = array(
			'someResource' => array(
				'role1' => array(
					'runtimeEvaluationsClosureCode' => FALSE,
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				)
			)
		);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService'), array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals(\TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT, $policyService->getPrivilegeForResource($role1, 'someResource'));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsADenyPrivilegeIfTheResourceHasRuntimeEvaluationsDefined() {
		$role1 = new \TYPO3\Flow\Security\Policy\Role('role1');

		$aclsCache = array(
			'someResource' => array(
				'role1' => array(
					'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				)
			)
		);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService'), array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals(\TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY, $policyService->getPrivilegeForResource($role1, 'someResource'));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsNullIfTheGivenRoleHasNoPrivilegesDefinedForTheGivenResource() {
		$role2 = new \TYPO3\Flow\Security\Policy\Role('role2');

		$aclsCache = array(
			'someResource' => array(
				'role1' => array(
					'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				)
			)
		);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService'), array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertNull($policyService->getPrivilegeForResource($role2, 'someResource'));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsADenyPrivilegeIfAskedForAResourceThatIsNotConnectedToAPolicyEntry() {
		$role1 = new \TYPO3\Flow\Security\Policy\Role('role1');

		$policyServiceClassName = $this->buildAccessibleProxy('TYPO3\Flow\Security\Policy\PolicyService');
		$policyService = new $policyServiceClassName();

		$policyService->_set('acls', array());
		$policyService->_set('resources', array('someResourceNotConnectedToAPolicyEntry' => 'someDefinition'));

		$this->assertEquals(\TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY, $policyService->getPrivilegeForResource($role1, 'someResourceNotConnectedToAPolicyEntry'));
	}

	/**
	 * @test
	 */
	public function initializeObjectLoadsTheEntityConstraintsFromTheCache() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('get')->with('acls')->will($this->returnValue(array('cachedAcls')));
		$mockCache->expects($this->at(2))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(3))->method('get')->with('entityResourcesConstraints')->will($this->returnValue(array('cachedConstraints')));

		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('Flow_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('setAclsForEverybodyRole'), array(), '', FALSE);
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);

		$policyService->initializeObject();

		$this->assertEquals($policyService->_get('entityResourcesConstraints'), array('cachedConstraints'));
	}

	/**
	 * @test
	 */
	public function initializeObjectCallsThePolicyExpressionParserAndBuildsTheEntityConstraintsIfTheCacheIsEmpty() {
		$policy = array(
			'resources' => array(
				'methods' => array(),
				'entities' => array('firstEntity', 'secondEntity')
			)
		);

		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('get')->with('acls')->will($this->returnValue(array('cachedAcls')));
		$mockCache->expects($this->at(2))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('Flow_Security_Policy')->will($this->returnValue($mockCache));

		$mockPolicyExpressionParser = $this->getMock('TYPO3\Flow\Security\Policy\PolicyExpressionParser');
		$mockPolicyExpressionParser->expects($this->once())->method('parseEntityResources')->with(array('firstEntity', 'secondEntity'))->will($this->returnValue(array('newParsedConstraints')));

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('setAclsForEverybodyRole'));
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);

		$policyService->initializeObject();

		$this->assertEquals($policyService->_get('entityResourcesConstraints'), array('newParsedConstraints'));
	}

	/**
	 * @test
	 */
	public function initializeObjectCallsParseEntityAclsIfTheAclCacheIsEmpty() {
		$mockConfigurationManager = $this->getMock('TYPO3\Flow\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue(array()));

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(1))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(2))->method('get')->with('entityResourcesConstraints')->will($this->returnValue(array('cachedConstraints')));

		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('Flow_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('parseEntityAcls', 'setAclsForEverybodyRole'));
		$policyService->expects($this->once())->method('parseEntityAcls');

		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);

		$policyService->initializeObject();
	}

	/**
	 * @test
	 */
	public function parseEntityAclsParsesTheEntityAclsCorrectly() {
		$policy = array(
			'acls' => array(
				'theRole' => array(
					'entities' => array(
						'theEntityResource' => 'GRANT',
						'theOtherEntityResource' => 'DENY'
					)
				),
				'theOtherRole' => array(
					'entities' => array(
						'theEntityResource' => 'DENY',
						'theOtherEntityResource' => 'GRANT'
					)
				)
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('policy', $policy);

		$policyService->_call('parseEntityAcls');

		$expectedAcls = array(
			'theEntityResource' => array(
				'theRole' => array('privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT),
				'theOtherRole' => array('privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY)
			),
			'theOtherEntityResource' => array(
				'theRole' => array('privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY),
				'theOtherRole' => array('privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT)
			)
		);

		$this->assertEquals($expectedAcls, $policyService->_get('acls'));
	}

	/**
	 * @test
	 */
	public function savePolicyCacheStoresTheEntityConstraintsAndACLsCorrectlyInTheCache() {
		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(1))->method('set')->with('acls', array('aclsArray'), array('TYPO3_Flow_Aop'));
		$mockCache->expects($this->at(2))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(3))->method('set')->with('entityResourcesConstraints', array('entityResourcesConstraintsArray'));

		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('Flow_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('buildEntityConstraints'));
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->_set('acls', array('aclsArray'));
		$policyService->_set('entityResourcesConstraints', array('entityResourcesConstraintsArray'));

		$policyService->savePolicyCache();
	}

	/**
	 * @test
	 */
	public function getResourcesConstraintsForEntityTypeAndRolesBasicallyWorks() {
		$entityResourcesConstraints = array(
			'TYPO3\MyEntity' => array(
				'resource1' => 'constraint1',
				'resource2' => 'constraint2',
				'resource3' => 'constraint3'
			)
		);

		$acls = array(
			'resource1' => array(
				'Administrator' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
			),
			'resource2' => array(
				'SomeOtherRole' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource3' => array(
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_ABSTAIN
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('buildEntityConstraints'));
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$result = $policyService->getResourcesConstraintsForEntityTypeAndRoles('TYPO3\MyEntity', array('Customer' => new \TYPO3\Flow\Security\Policy\Role('Customer'), 'Administrator' => new \TYPO3\Flow\Security\Policy\Role('Administrator')));
		$this->assertEquals($result, array('resource2' => 'constraint2', 'resource3' => 'constraint3'));
	}

	/**
	 * @test
	 */
	public function getResourcesConstraintsForEntityTypeAndRolesReturnsConstraintsForResourcesThatGotADenyAndAGrantPrivilege() {
		$entityResourcesConstraints = array(
			'TYPO3\MyEntity' => array(
				'resource1' => 'constraint1',
				'resource2' => 'constraint2',
				'resource3' => 'constraint3'
			)
		);

		$acls = array(
			'resource1' => array(
				'Administrator' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				)
			),
			'resource2' => array(
				'SomeOtherRole' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource3' => array(
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('buildEntityConstraints'));
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$result = $policyService->getResourcesConstraintsForEntityTypeAndRoles('TYPO3\MyEntity', array(new \TYPO3\Flow\Security\Policy\Role('Customer'), new \TYPO3\Flow\Security\Policy\Role('Administrator')));

		$this->assertEquals($result, array('resource1' => 'constraint1', 'resource2' => 'constraint2'));
	}

	/**
	 * @test
	 */
	public function hasPolicyEntryForEntityTypeWorks() {
		$entityResourcesConstraints = array(
			'TYPO3\MyEntity' => array(
				'resource1' => 'constraint1',
				'resource2' => 'constraint2',
				'resource3' => 'constraint3'
			)
		);

		$acls = array(
			'resource1' => array(
				'Administrator' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				)
			),
			'resource2' => array(
				'SomeOtherRole' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource3' => array(
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('buildEntityConstraints'));
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$managerRole = new \TYPO3\Flow\Security\Policy\Role('Manager');
		$administratorRole = new \TYPO3\Flow\Security\Policy\Role('Administrator');
		$customerRole = new \TYPO3\Flow\Security\Policy\Role('Customer');
		$kingRole = new \TYPO3\Flow\Security\Policy\Role('King');
		$anonymousRole = new \TYPO3\Flow\Security\Policy\Role('Anonymous');

		$this->assertTrue($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array($managerRole, $administratorRole, $anonymousRole)));
		$this->assertTrue($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array($managerRole, $customerRole)));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyOtherEntity', array($managerRole, $administratorRole, $anonymousRole)));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyOtherEntity', array($managerRole, $customerRole)));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array($managerRole, $anonymousRole)));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array($managerRole, $kingRole)));
	}

	/**
	 * @test
	 */
	public function isGeneralAccessForEntityTypeGrantedWorks() {
		$entityResourcesConstraints = array(
			'TYPO3\MyEntity' => array(
				'resource1' => \TYPO3\Flow\Security\Policy\PolicyService::MATCHER_ANY,
				'resource2' => 'constraint2',
				'resource3' => 'constraint3'
			)
		);

		$acls = array(
			'resource1' => array(
				'Administrator' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
				'AnotherRole' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_ABSTAIN
				)
			),
			'resource2' => array(
				'SomeOtherRole' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource3' => array(
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('buildEntityConstraints'));
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$anotherRole = new \TYPO3\Flow\Security\Policy\Role('Another');
		$administratorRole = new \TYPO3\Flow\Security\Policy\Role('Administrator');
		$customerRole = new \TYPO3\Flow\Security\Policy\Role('Customer');
		$someOtherRole = new \TYPO3\Flow\Security\Policy\Role('SomeOtherRole');

		$this->assertTrue($policyService->isGeneralAccessForEntityTypeGranted('TYPO3\MyEntity', array($administratorRole)));
		$this->assertTrue($policyService->isGeneralAccessForEntityTypeGranted('TYPO3\MyEntity', array($someOtherRole, $administratorRole, $anotherRole)));
		$this->assertFalse($policyService->isGeneralAccessForEntityTypeGranted('TYPO3\MyEntity', array($customerRole)));
		$this->assertFalse($policyService->isGeneralAccessForEntityTypeGranted('TYPO3\MyEntity', array($anotherRole)));
		$this->assertFalse($policyService->isGeneralAccessForEntityTypeGranted('TYPO3\MyEntity', array($someOtherRole)));
		$this->assertFalse($policyService->isGeneralAccessForEntityTypeGranted('TYPO3\MyEntity', array($someOtherRole, $customerRole, $administratorRole)));
	}

	/**
	 * @test
	 */
	public function isGeneralAccessForEntityTypeGrantedReturnsTrueIfNoAnyResourceTypeHasBeenDefinedForTheGivenEntity() {
		$entityResourcesConstraints = array(
			'TYPO3_MySecondEntity' => array(
				'resource4' => 'constraint4',
				'resource5' => 'constraint5'
			)
		);

		$acls = array(
			'resource4' => array(
				'SomeOtherRole' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
				'Administrator' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_ABSTAIN
				),
			),
			'resource5' => array(
				'Customer' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
				'Administrator' => array(
					'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('buildEntityConstraints'));
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$administratorRole = new \TYPO3\Flow\Security\Policy\Role('Administrator');
		$customerRole = new \TYPO3\Flow\Security\Policy\Role('Customer');
		$someOtherRole = new \TYPO3\Flow\Security\Policy\Role('SomeOtherRole');

		$this->assertTrue($policyService->isGeneralAccessForEntityTypeGranted('TYPO3_MySecondEntity', array($someOtherRole)));
		$this->assertTrue($policyService->isGeneralAccessForEntityTypeGranted('TYPO3_MySecondEntity', array($customerRole)));
		$this->assertTrue($policyService->isGeneralAccessForEntityTypeGranted('TYPO3_MySecondEntity', array($administratorRole)));
		$this->assertTrue($policyService->isGeneralAccessForEntityTypeGranted('TYPO3_MySecondEntity', array($someOtherRole, $customerRole, $administratorRole)));
	}

	/**
	 * @test
	 */
	public function hasPolicyEntryForMethodWorksCorrectlyIfNoRolesAreGiven() {
		$aclsCache = array(
						'firstclass->firstmethod' =>
							array(
								'role1' => array(
									'FirstResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							),
						'secondclass->secondmethod' =>
							array(
								'role2' => array(
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertTrue($policyService->hasPolicyEntryForMethod('firstClass', 'firstMethod'));
		$this->assertTrue($policyService->hasPolicyEntryForMethod('secondClass', 'secondMethod'));
		$this->assertFalse($policyService->hasPolicyEntryForMethod('thirdClass', 'thirdMethod'));
	}

	/**
	 * @test
	 */
	public function hasPolicyEntryForMethodWorksCorrectlyIfRolesAreGiven() {
		$aclsCache = array(
						'firstclass->firstmethod' =>
							array(
								'role1' => array(
									'FirstResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							),
						'secondclass->secondmethod' =>
							array(
								'role2' => array(
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertTrue($policyService->hasPolicyEntryForMethod('firstClass', 'firstMethod', array('role1')));
		$this->assertFalse($policyService->hasPolicyEntryForMethod('firstClass', 'firstMethod', array('role2')));

		$this->assertFalse($policyService->hasPolicyEntryForMethod('secondClass', 'secondMethod', array('role1')));
		$this->assertTrue($policyService->hasPolicyEntryForMethod('secondClass', 'secondMethod', array('role2')));

		$this->assertFalse($policyService->hasPolicyEntryForMethod('thirdClass', 'thirdMethod'));
	}

	/**
	 * @test
	 */
	public function hasPolicyEntryForMethodWorksWithCaseInsensitiveClassAndMethodNames() {
		$aclsCache = array(
						'firstclass->firstmethod' =>
							array(
								'role1' => array(
									'FirstResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							),
						'secondclass->secondmethod' =>
							array(
								'role2' => array(
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('acls', $aclsCache);

		$this->assertTrue($policyService->hasPolicyEntryForMethod('FirstClass', 'firstmethod', array('role1')));
		$this->assertFalse($policyService->hasPolicyEntryForMethod('firstClass', 'firstMethod', array('role2')));

		$this->assertFalse($policyService->hasPolicyEntryForMethod('SecondClass', 'seconDMethod', array('role1')));
		$this->assertTrue($policyService->hasPolicyEntryForMethod('secondclass', 'secondmethod', array('role2')));
	}

	/**
	 * @test
	 */
	public function reduceTargetClassNamesPassesTheGivenClassNameIndexToAllResourceFiltersAndReturnsTheUnionOfTheirResults() {
		$availableClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Class2',
			'TestPackage\Subpackage\SubSubPackage\Class3',
			'TestPackage\Subpackage2\Class4'
		);
		sort($availableClassNames);
		$availableClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
		$availableClassNamesIndex->setClassNames($availableClassNames);

		$mockPointcutFilter1 = $this->getMock('\TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface');
		$mockPointcutFilter1->expects($this->once())->method('reduceTargetClassNames')->with($availableClassNamesIndex)->will($this->returnValue(new \TYPO3\Flow\Aop\Builder\ClassNameIndex(array('TestPackage\Subpackage\Class1' => TRUE))));
		$mockPointcutFilter2 = $this->getMock('\TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface');
		$mockPointcutFilter2->expects($this->once())->method('reduceTargetClassNames')->with($availableClassNamesIndex)->will($this->returnValue(new \TYPO3\Flow\Aop\Builder\ClassNameIndex(array('TestPackage\Subpackage\SubSubPackage\Class3' => TRUE))));

		$policyFilterArray = array(
			'role' => array(
				'resource1' => $mockPointcutFilter1,
				'resource2' => $mockPointcutFilter2
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('filters', $policyFilterArray);

		$expectedClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Subpackage\SubSubPackage\Class3'
		);
		sort($expectedClassNames);
		$expectedClassNamesIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
		$expectedClassNamesIndex->setClassNames($expectedClassNames);

		$result = $policyService->reduceTargetClassNames($availableClassNamesIndex);

		$this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
	}

	/**
	 * @test
	 */
	public function getRolesReturnsAllRolesFromRepository() {
		$allRoles = array(
			'Anonymous' => new \TYPO3\Flow\Security\Policy\Role('Anonymous'),
			'Everybody' => new \TYPO3\Flow\Security\Policy\Role('Everybody'),
			'Acme.Demo:Test' => new \TYPO3\Flow\Security\Policy\Role('Acme.Demo:Test')
		);
		$allRolesCollection = new \Doctrine\Common\Collections\ArrayCollection($allRoles);
		$mockRoleRepository = $this->getMock('TYPO3\Flow\Security\Policy\RoleRepository');
		$mockRoleRepository->expects($this->any())->method('findAll')->will($this->returnValue($allRolesCollection));

		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy', 'initializeRolesFromPolicy'));
		$policyService->_set('roleRepository', $mockRoleRepository);

		$this->assertEquals($allRoles, $policyService->getRoles());
	}

	/**
	 * @test
	 */
	public function getRoleReturnsSystemRole() {
		$everybodyRole = new \TYPO3\Flow\Security\Policy\Role('Everybody');
		$anonymousRole = new \TYPO3\Flow\Security\Policy\Role('Anonymous');
		$systemRoles = array(
			'Everybody' => $everybodyRole,
			'Anonymous' => $anonymousRole
		);
		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('systemRoles', $systemRoles);

		$this->assertSame($everybodyRole, $policyService->getRole('Everybody'));
		$this->assertSame($anonymousRole, $policyService->getRole('Anonymous'));
	}

	/**
	 * @test
	 */
	public function getRoleReturnsRoleFromRepository() {
		$role = new \TYPO3\Flow\Security\Policy\Role('Acme.Demo:Test');

		$mockRoleRepository = $this->getMock('TYPO3\Flow\Security\Policy\RoleRepository');
		$mockRoleRepository->expects($this->any())->method('findByIdentifier')->with('Acme.Demo:Test')->will($this->returnValue($role));

		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('initializeRolesFromPolicy'));
		$policyService->_set('roleRepository', $mockRoleRepository);

		$this->assertSame($role, $policyService->getRole('Acme.Demo:Test'));
	}

	/**
	 * @expectedException \TYPO3\Flow\Security\Exception\NoSuchRoleException
	 * @test
	 */
	public function getRoleThrowsExceptionIfRoleIsUnknown() {
		$mockRoleRepository = $this->getMock('TYPO3\Flow\Security\Policy\RoleRepository');
		$mockRoleRepository->expects($this->any())->method('findByIdentifier')->will($this->returnValue(NULL));

		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('initializeRolesFromPolicy'));
		$policyService->_set('roleRepository', $mockRoleRepository);

		$policyService->getRole('Acme.Fizzle.Guzzle');
	}

	/**
	 * @expectedException \TYPO3\Flow\Security\Exception\NoSuchRoleException
	 * @test
	 */
	public function initializeRolesFromPolicyThrowsExceptionIfParentRoleIsNotYetKnown() {
		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$mockCache->expects($this->any())->method('has')->with('rolesFromPolicyUpToDate')->will($this->returnValue(FALSE));
		$mockCache->expects($this->any())->method('set')->with('rolesFromPolicyUpToDate', 'Yes, Sir!');

		$policy = array('roles' => array(
			'Acme.Demo:Test' => array('Acme.Demo:Parent')
		));

		$mockRoleRepository = $this->getMock('TYPO3\Flow\Security\Policy\RoleRepository');

		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('cache', $mockCache);
		$policyService->_set('roleRepository', $mockRoleRepository);
		$policyService->_set('policy', $policy);

		$policyService->_call('initializeRolesFromPolicy');
	}

	/**
	 * @test
	 */
	public function initializeRolesFromPolicyAddsRolesNotYetKnown() {
		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$mockCache->expects($this->atLeastOnce())->method('has')->with('rolesFromPolicyUpToDate')->will($this->returnValue(FALSE));
		$mockCache->expects($this->atLeastOnce())->method('set')->with('rolesFromPolicyUpToDate', 'Yes, Sir!');

		$everybodyRole = new \TYPO3\Flow\Security\Policy\Role('Everybody');
		$anonymousRole = new \TYPO3\Flow\Security\Policy\Role('Anonymous');

		$testRole = $this->getMock('TYPO3\Flow\Security\Policy\Role', array('setParentRoles'), array('Acme.Demo:Test'));
		$testRole->expects($this->once())->method('setParentRoles');
		$parentRole = new \TYPO3\Flow\Security\Policy\Role('Acme.Demo:Parent');

		$policy = array('roles' => array(
			'Acme.Demo:Test' => array('Acme.Demo:Parent'),
			'Acme.Demo:Parent' => array()
		));

			// using the sequence indexes is clumsy, but, ah, well.
		$mockRoleRepository = $this->getMock('TYPO3\Flow\Security\Policy\RoleRepository');
		$mockRoleRepository->expects($this->at(0))->method('findByIdentifier')->with('Anonymous')->will($this->returnValue($anonymousRole));
		$mockRoleRepository->expects($this->at(1))->method('findByIdentifier')->with('Everybody')->will($this->returnValue($everybodyRole));
		$mockRoleRepository->expects($this->at(2))->method('findByIdentifier')->with('Acme.Demo:Test')->will($this->returnValue(NULL));
		$mockRoleRepository->expects($this->at(3))->method('add');
		$mockRoleRepository->expects($this->at(4))->method('findByIdentifier')->with('Acme.Demo:Parent')->will($this->returnValue(NULL));
		$mockRoleRepository->expects($this->at(5))->method('add');
		$mockRoleRepository->expects($this->at(6))->method('findByIdentifier')->with('Acme.Demo:Parent')->will($this->returnValue($parentRole));
		$mockRoleRepository->expects($this->at(7))->method('findByIdentifier')->with('Acme.Demo:Test')->will($this->returnValue($testRole));

		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('dummy'));
		$policyService->_set('cache', $mockCache);
		$policyService->_set('roleRepository', $mockRoleRepository);
		$policyService->_set('policy', $policy);

		$policyService->_call('initializeRolesFromPolicy');
	}


	/**
	 * @expectedException \TYPO3\Flow\Security\Exception\RoleExistsException
	 * @test
	 */
	public function createRoleThrowsExceptionIfRoleIsSystemRole() {
		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('initializeRolesFromPolicy'));
		$policyService->_set('systemRoles', array('Anonymous' => new Role('Anonymous')));

		$policyService->createRole('Anonymous');
	}

	/**
	 * @expectedException \TYPO3\Flow\Security\Exception\RoleExistsException
	 * @test
	 */
	public function createRoleThrowsExceptionIfRoleExists() {
		$mockRoleRepository = $this->getMock('TYPO3\Flow\Security\Policy\RoleRepository');
		$mockRoleRepository->expects($this->any())->method('findByIdentifier')->will($this->returnValue(new \stdClass()));

		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('initializeRolesFromPolicy'));
		$policyService->_set('roleRepository', $mockRoleRepository);

		$policyService->createRole('Acme.Fizzle:Guzzle');
	}

	/**
	 * data provider
	 * @return array
	 */
	public function unqualifiedRoleIdentifiers() {
		return array(
			array('Dazzle'),
			array('Dizzle.Dazzle')
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @dataProvider unqualifiedRoleIdentifiers
	 * @test
	 */
	public function createRoleThrowsExceptionIfRoleIdentifierIsNotQualified($roleIdentifier) {
		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('initializeRolesFromPolicy'));

		$policyService->createRole($roleIdentifier);
	}

	/**
	 * @test
	 */
	public function createRoleAddsRoleToRepositoryAndReturnsRoleObject() {
		$newRole = new Role('Acme.Fizzle:Guzzle');

		$mockRoleRepository = $this->getMock('TYPO3\Flow\Security\Policy\RoleRepository');
		$mockRoleRepository->expects($this->any())->method('findByIdentifier')->will($this->returnValue(NULL));
		$mockRoleRepository->expects($this->once())->method('add')->with($this->equalTo($newRole));

		/** @var $policyService \TYPO3\Flow\Security\Policy\PolicyService */
		$policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('initializeRolesFromPolicy'));
		$policyService->_set('roleRepository', $mockRoleRepository);

		$createdRole = $policyService->createRole('Acme.Fizzle:Guzzle');

		$this->assertEquals($newRole, $createdRole);
	}

}
?>
