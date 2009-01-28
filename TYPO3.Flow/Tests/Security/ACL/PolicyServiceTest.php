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
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 */

/**
 * Testcase for for the policy service
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
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

		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
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

		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
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
}
?>