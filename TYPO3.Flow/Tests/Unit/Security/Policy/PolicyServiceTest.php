<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Policy;

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
 * Testcase for for the policy service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PolicyServiceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function thePolicyIsLoadedCorrectlyFromTheConfigurationManager() {
		$policy = array(
			'roles' => array('THE_ROLE' => array()),
			'resources' => array(
				'methods' => array('theResource' => 'method(Foo->bar())'),
			),
			'acls' => array('THE_ROLE' => array('theResource' => 'ACCESS_GRANT'))
		);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnValue($policy));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->once())->method('has')->will($this->returnValue(FALSE));

		$policyService = new \F3\FLOW3\Security\Policy\PolicyService();
		$policyService->injectCache($mockCache);
		$policyService->injectConfigurationManager($mockConfigurationManager);

		$policyService->initializeObject();
	}

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
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
			),
			'acls' => array('TheRole' => array('theResource' => 'GRANT'))
		);

		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('F3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);
		$mockPolicyExpressionParser->expects($this->once())->method('setResourcesTree')->with($policy['resources']['methods']);
		$mockPolicyExpressionParser->expects($this->once())->method('parse')->with('theResource')->will($this->returnValue($mockFilter));

		$accessibleProxyClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService');
		$policyService = new $accessibleProxyClassName();
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);

		$this->assertTrue($policyService->matches('Foo', 'bar', 'Baz', 1));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchesAddsRuntimeEvaluationsCorrectlyToTheInternalPolicyCache() {
		$settings = array(
			'security' => array(
				'enable' => TRUE
			)
		);

		$policy = array(
			'acls' => array('TheRole' => array(
				'FirstResource' => 'GRANT',
				'SecondResource' => 'DENY',
				'ThirdResource' => 'DENY'
			))
		);

		$mockFilter1 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter1->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));
		$mockFilter1->expects($this->once())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(TRUE));
		$mockFilter1->expects($this->once())->method('getRuntimeEvaluationsClosureCode')->will($this->returnValue('closureCode1'));

		$mockFilter2 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter2->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));
		$mockFilter2->expects($this->once())->method('hasRuntimeEvaluationsDefinition')->will($this->returnValue(FALSE));
		$mockFilter2->expects($this->never())->method('getRuntimeEvaluationsClosureCode');

		$mockFilter3 = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
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

		$accessibleProxyClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService');
		$policyService = new $accessibleProxyClassName();
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);
		$policyService->_set('filters', $filters);

		$policyService->matches('Foo', 'bar', 'Baz', 1);

		$expectedACLCache = array(
			'Foo->bar' => array(
				'TheRole' => array(
					'FirstResource' => array(
						'privilege' => PolicyService::PRIVILEGE_GRANT,
						'runtimeEvaluationsClosureCode' => 'closureCode1'
					),
					'SecondResource' => array(
						'privilege' => PolicyService::PRIVILEGE_DENY,
						'runtimeEvaluationsClosureCode' => FALSE
					),
					'ThirdResource' => array(
						'privilege' => PolicyService::PRIVILEGE_DENY,
						'runtimeEvaluationsClosureCode' => 'closureCode3'
					)
				)
			)
		);

		$this->assertEquals($policyService->_get('acls'), $expectedACLCache);
	}

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesAlwaysReturnsFalseIfSecurityIsDisabled() {
		$settings = array('security' => array('enable' => FALSE));

		$policyService = new \F3\FLOW3\Security\Policy\PolicyService();
		$policyService->injectSettings($settings);
		$this->assertFalse($policyService->matches('Foo', 'bar', 'Baz', 1));
	}

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
			),
			'acls' => array('theRole' => array('theResource' => 'GRANT'))
		);

		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('F3\FLOW3\Security\Policy\PolicyExpressionParser', array(), array(), '', FALSE);
		$mockPolicyExpressionParser->expects($this->once())->method('setResourcesTree')->with($policy['resources']['methods']);
		$mockPolicyExpressionParser->expects($this->once())->method('parse')->with('theResource')->will($this->returnValue($mockFilter));

		$accessibleProxyClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService');
		$policyService = new $accessibleProxyClassName();
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);
		$policyService->injectSettings($settings);
		$policyService->_set('policy', $policy);

		$policyService->matches('Foo', 'bar', 'Baz', 1);

		$expectedPolicies = array(
			'Foo->bar' => array(
				'theRole' => array(
					'theResource' => array (
						'privilege' => PolicyService::PRIVILEGE_GRANT,
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
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForJoinPointReturnsAnEmptyArrayIfNoPrivilegesCouldBeFound() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', array('className->methodName' => array()));

		$this->assertEquals(array(), $policyService->getPrivilegesForJoinPoint($this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE), $mockJoinPoint));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForJoinPointReturnsThePrivilegesArrayThatHasBeenParsedForTheGivenJoinPointAndRole() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$mockRole = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$privilegesArray = array('FirstResource' => PolicyService::PRIVILEGE_GRANT, 'SecondResource' => PolicyService::PRIVILEGE_DENY, 'ThirdResource' => PolicyService::PRIVILEGE_GRANT);

		$aclsCache = array(
						'className->methodName' =>
							array(
								'role1' => array(
									'FirstResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => PolicyService::PRIVILEGE_GRANT
									),
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => PolicyService::PRIVILEGE_DENY
									),
									'ThirdResource' => array(
										'runtimeEvaluationsClosureCode' => FALSE,
										'privilege' => PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForJoinPoint($mockRole, $mockJoinPoint));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForJoinPointReturnsOnlyPrivilgesThatPassedRuntimeEvaluationsInThePrivilegesArrayThatHasBeenParsedForTheGivenJoinPointAndRole() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getClassName')->will($this->returnValue('className'));
		$mockJoinPoint->expects($this->once())->method('getMethodName')->will($this->returnValue('methodName'));

		$mockRole = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$privilegesArray = array('SecondResource' => PolicyService::PRIVILEGE_GRANT);

		$aclsCache = array(
						'className->methodName' => array(
								'role1' => array(
									'FirstResource' => array(
										'runtimeEvaluationsClosureCode' => 'function () { return FALSE; };',
										'privilege' => PolicyService::PRIVILEGE_DENY
									),
									'SecondResource' => array(
										'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
										'privilege' => PolicyService::PRIVILEGE_GRANT
									)
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForJoinPoint($mockRole, $mockJoinPoint));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegeForResourceReturnsThePrivilegeThatHasBeenParsedForTheGivenResource() {
		$mockRole = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$aclsCache = array(
						'someResource' => array(
								'role1' => array(
									'runtimeEvaluationsClosureCode' => FALSE,
									'privilege' => PolicyService::PRIVILEGE_GRANT
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals(PolicyService::PRIVILEGE_GRANT, $policyService->getPrivilegeForResource($mockRole, 'someResource'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegeForResourceReturnsADenyPrivilegeIfTheResourceHasRuntimeEvaluationsDefined() {
		$mockRole = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$aclsCache = array(
						'someResource' => array(
								'role1' => array(
									'runtimeEvaluationsClosureCode' => 'function () { return TRUE; };',
									'privilege' => PolicyService::PRIVILEGE_GRANT
								)
							)
						);

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService'), array('dummy'), array(), '', FALSE);
		$policyService->_set('acls', $aclsCache);

		$this->assertEquals(PolicyService::PRIVILEGE_DENY, $policyService->getPrivilegeForResource($mockRole, 'someResource'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegeForResourceReturnsNullIfAskedForAResourceThatIsNotConnectedToAPolicyEntry() {
		$mockRole = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$policyServiceClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\Policy\PolicyService');
		$policyService = new $policyServiceClassName();
		$policyService->injectObjectManager($mockObjectManager);

		$policyService->_set('acls', array());
		$policyService->_set('resources', array('someResourceNotConnectedToAPolicyEntry' => 'someDefinition'));

		$this->assertEquals(PolicyService::PRIVILEGE_DENY, $policyService->getPrivilegeForResource($mockRole, 'someResourceNotConnectedToAPolicyEntry'));

	}
}
?>