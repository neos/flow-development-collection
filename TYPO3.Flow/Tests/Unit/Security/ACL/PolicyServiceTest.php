<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\ACL;

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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesAsksThePolicyExpressionParserToBuildPointcutFiltersAndChecksIfTheyMatchTheGivenClassAndMethod() {
		$settings = array(
			'security' => array(
				'enable' => TRUE,
				'policy' => array(
					'roles' => array('THE_ROLE' => array()),
					'resources' => array('theResource' => 'method(Foo->bar())'),
					'acls' => array('THE_ROLE' => array('theResource' => 'ACCESS_GRANT'))
				)
			)
		);

		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('F3\FLOW3\Security\ACL\PolicyExpressionParser', array(), array(), '', FALSE);
		$mockPolicyExpressionParser->expects($this->once())->method('setResourcesTree')->with($settings['security']['policy']['resources']);
		$mockPolicyExpressionParser->expects($this->once())->method('parse')->with('theResource')->will($this->returnValue($mockFilter));

		$policyService = new \F3\FLOW3\Security\ACL\PolicyService();
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);
		$policyService->injectSettings($settings);

		$this->assertTrue($policyService->matches('Foo', 'bar', 'Baz', 1));
	}

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesAlwaysReturnsFalseIfSecurityIsDisabled() {
		$settings = array('security' => array('enable' => FALSE));

		$policyService = new \F3\FLOW3\Security\ACL\PolicyService();
		$policyService->injectSettings($settings);
		$this->assertFalse($policyService->matches('Foo', 'bar', 'Baz', 1));
	}

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesStoresMatchedACLsInAnArrayForLaterCaching() {
		$settings = array(
			'security' => array(
				'enable' => TRUE,
				'policy' => array(
					'roles' => array('THE_ROLE' => array()),
					'resources' => array('theResource' => 'method(Foo->bar())'),
					'acls' => array('THE_ROLE' => array('theResource' => 'ACCESS_GRANT'))
				)
			)
		);

		$mockFilter = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockFilter->expects($this->once())->method('matches')->with('Foo', 'bar', 'Baz')->will($this->returnValue(TRUE));

		$mockPolicyExpressionParser = $this->getMock('F3\FLOW3\Security\ACL\PolicyExpressionParser', array(), array(), '', FALSE);
		$mockPolicyExpressionParser->expects($this->once())->method('setResourcesTree')->with($settings['security']['policy']['resources']);
		$mockPolicyExpressionParser->expects($this->once())->method('parse')->with('theResource')->will($this->returnValue($mockFilter));

		$policyService = new \F3\FLOW3\Security\ACL\PolicyService();
		$policyService->injectPolicyExpressionParser($mockPolicyExpressionParser);
		$policyService->injectSettings($settings);

		$policyService->matches('Foo', 'bar', 'Baz', 1);

		$expectedACLs = array(
			'Foo->bar' => array(
				'THE_ROLE' => array('ACCESS_GRANT')
			)
		);

		$aclsReflection = new \ReflectionProperty($policyService, 'acls');
		$this->assertSame($expectedACLs, $aclsReflection->getValue($policyService));
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

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\ACL\PolicyService'), array('parsePrivileges'), array(), '', FALSE);
		$policyService->expects($this->once())->method('parsePrivileges')->will($this->returnValue(NULL));
		$policyService->_set('acls', array('className->methodName' => array()));

		$this->assertEquals(array(), $policyService->getPrivilegesForJoinPoint($this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), '', FALSE), $mockJoinPoint));
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

		$mockRole = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$privilegesArray = array('privilege1', 'privilege2', 'privilege3');

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\ACL\PolicyService'), array('parsePrivileges'), array(), '', FALSE);
		$policyService->expects($this->once())->method('parsePrivileges')->with('className->methodName', 'role1')->will($this->returnValue($privilegesArray));
		$policyService->_set('acls', array('className->methodName' => array()));

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForJoinPoint($mockRole, $mockJoinPoint));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForResourceReturnsAnEmptyArrayIfNoPrivilegesCouldBeFound() {
		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\ACL\PolicyService'), array('parsePrivileges'), array(), '', FALSE);
		$policyService->expects($this->once())->method('parsePrivileges')->will($this->returnValue(NULL));
		$policyService->_set('acls', array('someResource' => array()));

		$this->assertEquals(array(), $policyService->getPrivilegesForResource($this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), '', FALSE), 'someResource'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForResourceReturnsThePrivilegesArrayThatHasBeenParsedForTheGivenJoinPointAndRole() {
		$mockRole = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), '', FALSE);
		$mockRole->expects($this->once())->method('__toString')->will($this->returnValue('role1'));

		$privilegesArray = array('privilege1', 'privilege2', 'privilege3');

		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\ACL\PolicyService'), array('parsePrivileges'), array(), '', FALSE);
		$policyService->expects($this->once())->method('parsePrivileges')->with('someResource', 'role1')->will($this->returnValue($privilegesArray));
		$policyService->_set('acls', array('someResource' => array()));

		$this->assertEquals($privilegesArray, $policyService->getPrivilegesForResource($mockRole, 'someResource'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parsePrivilegesReturnsNullIfNoPolicyEntryCouldBeFound() {
		$policyService = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\ACL\PolicyService'), array('parsePrivileges'), array(), '', FALSE);
		$policyService->_set('acls', array('className->methodName' => array(), 'someResource' => array()));

		$this->assertNull($policyService->_call('parsePrivileges', 'className->methodName', 'someRole', ''));
		$this->assertNull($policyService->_call('parsePrivileges', 'someResource', 'someOtherRole', ''));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parsePrivilegesReturnsTheCorrectPrivilegesArray() {
		$policyServiceClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\ACL\PolicyService');
		$policyService = new $policyServiceClassName();
		$policyService->injectObjectFactory($this->objectFactory);

		$policyService->_set('acls', array('className->methodName' => array('parentRole2' => array('ACCESS_GRANT'), 'myRole' => array('ACCESS_DENY'), 'parentRole1' => array('CUSTOMPRIVILEGE_GRANT'))));
		$policyService->_set('roles', array('myRole' => array('parentRole1', 'parentRole2'), 'parentRole1' => NULL, 'parentRole2' => array()));

		$expectedPrivileges = array(
			new \F3\FLOW3\Security\ACL\Privilege('ACCESS', TRUE),
			new \F3\FLOW3\Security\ACL\Privilege('CUSTOMPRIVILEGE', TRUE),
			new \F3\FLOW3\Security\ACL\Privilege('ACCESS', FALSE),
		);

		$this->assertEquals($expectedPrivileges, $policyService->_call('parsePrivileges', 'className->methodName', 'myRole', ''));

	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForResourceReturnsAnAccessDenyPrivilegeIfAskedForAResourceThatIsNotConnectedToAnACLEntry() {
		$mockRole = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), '', FALSE);

		$policyServiceClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\ACL\PolicyService');
		$policyService = new $policyServiceClassName();
		$policyService->injectObjectFactory($this->objectFactory);

		$policyService->_set('acls', array());
		$policyService->_set('resources', array('someResourceNotConnectedToAnACLEntry' => 'someDefinition'));

		$expectedPrivilege = array(
			new \F3\FLOW3\Security\ACL\Privilege('ACCESS', FALSE),
		);

		$this->assertEquals($expectedPrivilege, $policyService->_call('getPrivilegesForResource', $mockRole, 'someResourceNotConnectedToAnACLEntry'));

	}
}
?>