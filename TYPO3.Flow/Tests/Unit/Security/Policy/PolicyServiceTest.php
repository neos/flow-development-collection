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
 * Testcase for for the policy service
 *
 */
class PolicyServiceTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function thePolicyIsLoadedCorrectlyFromTheConfigurationManager() {
		$mockPolicyExpressionParser = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);

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

		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = new \TYPO3\FLOW3\Security\Policy\PolicyService();
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);

		$policyService->initializeObject();
	}

	/**
	 * @test
	 */
	public function initializeObjectSetsTheEverybodyRoleInThePolicy() {
		$mockPolicyExpressionParser = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);

		$policy = array(
			'roles' => array(),
			'resources' => array(
				'methods' => array(),
				'entities' => array()
			),
			'acls' => array()
		);

		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('parseEntityAcls'), array(), '', FALSE);
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
		$mockPolicyExpressionParser = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);

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

		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('parseEntityAcls'), array(), '', FALSE);
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

		$mockFilter = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);
		$mockPolicyExpressionParser->expects($this->once())->method('parseMethodResources')->with('theResource', $policy['resources']['methods'])->will($this->returnValue($mockFilter));

		$accessibleProxyClassName = $this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService');
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

		$mockFilter1 = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter1->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));
		$mockFilter1->expects($this->once())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));
		$mockFilter1->expects($this->once())->method('getRuntimeEvaluationsClosureCode')->will($this->returnValue('closureCode1'));

		$mockFilter2 = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter2->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));
		$mockFilter2->expects($this->once())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(FALSE));
		$mockFilter2->expects($this->never())->method('getRuntimeEvaluationsClosureCode');

		$mockFilter3 = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
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

		$accessibleProxyClassName = $this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService');
		$policyService = new $accessibleProxyClassName();
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);
		$policyService->_set('filters', $filters);

		$policyService->matches('Foo', 'bar', 'Baz', 1);

		$expectedACLCache = array(
			'foo->bar' => array(
				'TheRole' => array(
					'FirstResource' => array(
						'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT,
						'runtimeEvaluationsClosureCode' => 'closureCode1'
					),
					'SecondResource' => array(
						'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY,
						'runtimeEvaluationsClosureCode' => FALSE
					),
					'ThirdResource' => array(
						'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY,
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

		$policyService = new \TYPO3\FLOW3\Security\Policy\PolicyService();
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

		$mockFilter = $this->getMock('TYPO3\FLOW3\Aop\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);
		$mockPolicyExpressionParser->expects($this->once())->method('parseMethodResources')->with('theResource', $policy['resources']['methods'])->will($this->returnValue($mockFilter));

		$accessibleProxyClassName = $this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService');
		$policyService = new $accessibleProxyClassName();
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);

		$policyService->matches('Foo', 'bar', 'Baz', 1);

		$expectedPolicies = array(
			'foo->bar' => array(
				'theRole' => array(
					'theResource' => array (
						'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT,
                        'runtimeEvaluationsClosureCode' => FALSE
                    )
				)
			)
		);

		$aclsReflection = new \ReflectionProperty($policyService, 'acls');
		$this->assertSame($expectedPolicies, $aclsReflection->getValue($policyService));
	}

	/**
	 * @test
	 */
	public function getPrivilegesForJoinPointReturnsAnEmptyArrayIfNoPrivilegesCouldBeFound() {
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', array('classname->methodname' => array()));

		$this->assertEquals(array(), $policyService->getPrivilegesForJoinPoint($this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE), $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function getPrivilegesForJoinPointReturnsThePrivilegesArrayThatHasBeenParsedForTheGivenJoinPointAndRole() {
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$mockRole = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$privilegesArray = array('FirstResource' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT, 'SecondResource' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY, 'ThirdResource' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT);

		$aclsCache = array(
						'classname->methodname' =>
							array(
								'role1' => array(
									'FirstResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									),
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
									),
									'ThirdResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForJoinPoint($mockRole, $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function getPrivilegesForJoinPointReturnsOnlyPrivilgesThatPassedRuntimeEvaluationsInThePrivilegesArrayThatHasBeenParsedForTheGivenJoinPointAndRole() {
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$mockRole = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$privilegesArray = array('SecondResource' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT);

		$aclsCache = array(
						'classname->methodname' => array(
								'role1' => array(
									'FirstResource' => array(
										'runtimeEvaluationsClosureCode' => 'function () { return FALSE; };',
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
									),
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForJoinPoint($mockRole, $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsThePrivilegeThatHasBeenParsedForTheGivenResource() {
		$mockRole = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$aclsCache = array(
						'someResource' => array(
								'role1' => array(
									'runtimeEvaluationsClosureCode' => FALSE,
									'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals(\TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT, $policyService->getPrivilegeForResource($mockRole, 'someResource'));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsADenyPrivilegeIfTheResourceHasRuntimeEvaluationsDefined() {
		$mockRole = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$aclsCache = array(
						'someResource' => array(
								'role1' => array(
									'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
									'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals(\TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY, $policyService->getPrivilegeForResource($mockRole, 'someResource'));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsNullIfTheGivenRoleHasNoPriviligesDefinedForTheGivenResource() {
		$mockRole = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role2'));

		$aclsCache = array(
						'someResource' => array(
								'role1' => array(
									'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
									'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertNull($policyService->getPrivilegeForResource($mockRole, 'someResource'));
	}

	/**
	 * @test
	 */
	public function getPrivilegeForResourceReturnsADenyPrivilegeIfAskedForAResourceThatIsNotConnectedToAPolicyEntry() {
		$mockRole = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);

		$policyServiceClassName = $this->buildAccessibleProxy('TYPO3\FLOW3\Security\Policy\PolicyService');
		$policyService = new $policyServiceClassName();

		$policyService->_set('acls', array());
		$policyService->_set('resources', array('someResourceNotConnectedToAPolicyEntry' => 'someDefinition'));

		$this->assertEquals(\TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY, $policyService->getPrivilegeForResource($mockRole, 'someResourceNotConnectedToAPolicyEntry'));
	}

	/**
	 * @test
	 */
	public function initializeObjectLoadsTheEntityConstraintsFromTheCache() {
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('get')->with('acls')->will($this->returnValue(array('cachedAcls')));
		$mockCache->expects($this->at(2))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(3))->method('get')->with('entityResourcesConstraints')->will($this->returnValue(array('cachedConstraints')));

		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('setAclsForEverybodyRole'), array(), '', FALSE);
		$policyService->injectCacheManager($mockCacheManager);
		$policyService->injectConfigurationManager($mockConfigurationManager);

		$policyService->initializeObject();

		$this->assertEquals($policyService->_get('entityResourcesConstraints'), array('cachedConstraints'));
	}

	/**
	 * @test
	 */
	public function initializeObjectCallsThePolicyExpressionPraserAndBuildsTheEntityConstraintsIfTheCacheIsEmpty() {
		$policy = array(
			'resources' => array(
				'methods' => array(),
				'entities' => array('firstEntity', 'secondEntity')
			)
		);

		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('get')->with('acls')->will($this->returnValue(array('cachedAcls')));
		$mockCache->expects($this->at(2))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(FALSE));

		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Security_Policy')->will($this->returnValue($mockCache));

		$mockPolicyExpressionParser = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);
		$mockPolicyExpressionParser->expects($this->once())->method('parseEntityResources')->with(array('firstEntity', 'secondEntity'))->will($this->returnValue(array('newParsedConstraints')));

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('setAclsForEverybodyRole'), array(), '', FALSE);
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
		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue(array()));

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(1))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(2))->method('get')->with('entityResourcesConstraints')->will($this->returnValue(array('cachedConstraints')));

		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('parseEntityAcls', 'setAclsForEverybodyRole'), array(), '', FALSE);
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

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('dummy'), array(), '', FALSE);
		$policyService->_set('policy', $policy);

		$policyService->_call('parseEntityAcls');

		$expectedAcls = array(
			'theEntityResource' => array(
				'theRole' => array('privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT),
				'theOtherRole' => array('privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY)
			),
			'theOtherEntityResource' => array(
				'theRole' => array('privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY),
				'theOtherRole' => array('privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT)
			)
		);

		$this->assertEquals($expectedAcls, $policyService->_get('acls'));
	}

	/**
	 * @test
	 */
	public function savePolicyCacheStoresTheEntityConstraintsAndACLsCorrectlyInTheCache() {
		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('acls')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(1))->method('set')->with('acls', array('aclsArray'), array('TYPO3_FLOW3_Aop'));
		$mockCache->expects($this->at(2))->method('has')->with('entityResourcesConstraints')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(3))->method('set')->with('entityResourcesConstraints', array('entityResourcesConstraintsArray'));

		$mockCacheManager = $this->getMock('TYPO3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Security_Policy')->will($this->returnValue($mockCache));

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('buildEntityConstraints'), array(), '', FALSE);
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
			'TYPO3_MyEntity' => array(
				'resource1' => 'constraint1',
				'resource2' => 'constraint2',
				'resource3' => 'constraint3'
			)
		);

		$acls = array(
			'resource1' => array(
				'Administrator' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource2' => array(
				'SomeOtherRole' => array(
                    'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource3' => array(
				'Customer' => array(
                    'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('buildEntityConstraints'), array(), '', FALSE);
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$result = $policyService->getResourcesConstraintsForEntityTypeAndRoles('TYPO3\MyEntity', array('Customer', 'Administrator'));

		$this->assertEquals($result, array('resource1' => 'constraint1', 'resource3' => 'constraint3'));
	}

	/**
	 * @test
	 */
	public function getResourcesConstraintsForEntityTypeAndRolesDoesNotReturnConstraintsForResourcesThatGotADenyAndAGrantPrivilege() {
		$entityResourcesConstraints = array(
			'TYPO3_MyEntity' => array(
				'resource1' => 'constraint1',
				'resource2' => 'constraint2',
				'resource3' => 'constraint3'
			)
		);

		$acls = array(
			'resource1' => array(
				'Administrator' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
				'Customer' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				)
			),
			'resource2' => array(
				'SomeOtherRole' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource3' => array(
				'Customer' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('buildEntityConstraints'), array(), '', FALSE);
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$result = $policyService->getResourcesConstraintsForEntityTypeAndRoles('TYPO3\MyEntity', array('Customer', 'Administrator'));

		$this->assertEquals($result, array('resource3' => 'constraint3'));
	}

	/**
	 * @test
	 */
	public function hasPolicyEntryForEntityTypeWorks() {
		$entityResourcesConstraints = array(
			'TYPO3_MyEntity' => array(
				'resource1' => 'constraint1',
				'resource2' => 'constraint2',
				'resource3' => 'constraint3'
			)
		);

		$acls = array(
			'resource1' => array(
				'Administrator' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
				),
				'Customer' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				)
			),
			'resource2' => array(
				'SomeOtherRole' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			),
			'resource3' => array(
				'Customer' => array(
					'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY
				),
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('buildEntityConstraints'), array(), '', FALSE);
		$policyService->_set('entityResourcesConstraints', $entityResourcesConstraints);
		$policyService->_set('acls', $acls);

		$this->assertTrue($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array('Manager', 'Administrator', 'Anonymous')));
		$this->assertTrue($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array('Manager', 'Customer')));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyOtherEntity', array('Manager', 'Administrator', 'Anonymous')));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyOtherEntity', array('Manager', 'Customer')));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array('Manager', 'Anonymous')));
		$this->assertFalse($policyService->hasPolicyEntryForEntityType('TYPO3\MyEntity', array('Manager', 'King')));
	}

	/**
	 * @test
	 */
	public function getAllParentRolesUnnestsRoleInheritanceCorrectly() {
		$policy = array(
			'roles' => array(
				'Manager' => array(),
				'Administrator' => array('Chief', 'Manager'),
				'Customer' => array(),
				'User' => array('Customer'),
				'Employee' => array('Administrator', 'User'),
				'Chief' => array()
			),
			'resources' => array(),
			'acls' => array()
		);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('dummy'), array(), '', FALSE);
		$policyService->_set('policy', $policy);

		$expectedResult = array(
			'Manager' => new \TYPO3\FLOW3\Security\Policy\Role('Manager'),
			'Administrator' => new \TYPO3\FLOW3\Security\Policy\Role('Administrator'),
			'Customer' => new \TYPO3\FLOW3\Security\Policy\Role('Customer'),
			'User' => new \TYPO3\FLOW3\Security\Policy\Role('User'),
			'Chief' => new \TYPO3\FLOW3\Security\Policy\Role('Chief'),
		);

		$result = $policyService->getAllParentRoles(new \TYPO3\FLOW3\Security\Policy\Role('Employee'));

		sort($expectedResult);
		sort($result);

		$this->assertEquals($result, $expectedResult);
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
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							),
						'secondclass->secondmethod' =>
							array(
								'role2' => array(
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('dummy'), array(), '', FALSE);
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
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							),
						'secondclass->secondmethod' =>
							array(
								'role2' => array(
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('dummy'), array(), '', FALSE);
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
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							),
						'secondclass->secondmethod' =>
							array(
								'role2' => array(
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('dummy'), array(), '', FALSE);
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
		$availableClassNamesIndex = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$availableClassNamesIndex->setClassNames($availableClassNames);

		$mockPointcutFilter1 = $this->getMock('\TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter1->expects($this->once())->method('reduceTargetClassNames')->with($availableClassNamesIndex)->will($this->returnValue(new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex(array('TestPackage\Subpackage\Class1' => TRUE))));
		$mockPointcutFilter2 = $this->getMock('\TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface', array(), array(), '', FALSE);
		$mockPointcutFilter2->expects($this->once())->method('reduceTargetClassNames')->with($availableClassNamesIndex)->will($this->returnValue(new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex(array('TestPackage\Subpackage\SubSubPackage\Class3' => TRUE))));

		$policyFilterArray = array(
			'role' => array(
				'resource1' => $mockPointcutFilter1,
				'resource2' => $mockPointcutFilter2
			)
		);

		$policyService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Policy\PolicyService', array('dummy'), array(), '', FALSE);
		$policyService->_set('filters', $policyFilterArray);

		$expectedClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Subpackage\SubSubPackage\Class3'
		);
		sort($expectedClassNames);
		$expectedClassNamesIndex = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$expectedClassNamesIndex->setClassNames($expectedClassNames);

		$result = $policyService->reduceTargetClassNames($availableClassNamesIndex);

		$this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
	}
}
?>
