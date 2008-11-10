<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::ACL;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for for the policy service
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PolicyServiceTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchesReturnsTrueForAnACLEntryReferingToAResourceRepresentedByANotNestedPointcutExpression() {
		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array(), array(), '', FALSE);
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array(
			'aop' => array('proxyCache' => array('enable' => FALSE)),
			'security' => array(
				'policy' => array(
					'roles' => array('EXAMPLE_ROLE' => array()),
					'resources' => array('theOneAndOnlyResource' => 'method(F3::TestPackage::BasicClass->setSomeProperty())'),
					'acls' => array('EXAMPLE_ROLE' => array('theOneAndOnlyResource' => 'ACCESS_GRANT'))
				)
			)
		);
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$class = new F3::FLOW3::Reflection::ClassReflection('F3::TestPackage::BasicClass');
		$method = new F3::FLOW3::Reflection::MethodReflection('F3::TestPackage::BasicClass', 'setSomeProperty');

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);
		$this->assertTrue($policyService->matches($class, $method, 1));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchesReturnsTrueForAnACLEntryReferingToAResourceRepresentedByANestedPointcutExpression() {
		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array(), array(), '', FALSE);
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['aop']['proxyCache']['enable'] = FALSE;
		$settings['security']['policy']['roles'] = array('EXAMPLE_ROLE' => array());
		$settings['security']['policy']['resources'] = array(
			'theOneAndOnlyResource' => 'method(F3::TestPackage::BasicClass->setSomeProperty())',
			'theOtherLonelyResource' => 'method(F3::TestPackage::BasicClassValidator->.*())',
			'theIntegrativeResource' => 'theOneAndOnlyResource || theOtherLonelyResource',
		);
		$settings['security']['policy']['acls']['EXAMPLE_ROLE'] = array(
			'theIntegrativeResource' => 'ACCESS_GRANT',
		);

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$class = new F3::FLOW3::Reflection::ClassReflection('F3::TestPackage::BasicClass');
		$method = new F3::FLOW3::Reflection::MethodReflection('F3::TestPackage::BasicClass', 'setSomeProperty');
		$class2 = new F3::FLOW3::Reflection::ClassReflection('F3::TestPackage::BasicClassValidator');
		$method2 = new F3::FLOW3::Reflection::MethodReflection('F3::TestPackage::BasicClassValidator', 'validate');

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);

		$this->assertTrue($policyService->matches($class, $method, 1));
		$this->assertTrue($policyService->matches($class2, $method2, 2));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchesCreatesTheCorrectACLCacheArray() {
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['aop']['proxyCache']['enable'] = TRUE;
		$settings['security']['policy']['aclCache']['backend'] = '';
		$settings['security']['policy']['aclCache']['backendOptions'] = array();
		$settings['security']['policy']['roles'] = array('EXAMPLE_ROLE' => array());
		$settings['security']['policy']['resources'] = array(
			'theOneAndOnlyResource' => 'method(F3::TestPackage::BasicClass->setSomeProperty())',
			'theOtherLonelyResource' => 'method(F3::TestPackage::BasicClassValidator->.*())',
			'theIntegrativeResource' => 'theOneAndOnlyResource || theOtherLonelyResource',
		);
		$settings['security']['policy']['acls']['EXAMPLE_ROLE'] = array(
			'theIntegrativeResource' => 'ACCESS_GRANT',
		);

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$expectedACLCacheArray = array (
			'F3::TestPackage::BasicClass->setSomeProperty' => array(
				'EXAMPLE_ROLE' => array('ACCESS_GRANT'),
			),
		);

		$mockCache = $this->getMock('F3::FLOW3::Cache::AbstractCache', array(), array(), '', FALSE);
		$mockCache->expects($this->once())->method('set')->with('FLOW3_Security_Policy_ACLs', $expectedACLCacheArray);

		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array('create'), array(), '', FALSE);
		$mockCacheFactory->expects($this->atLeastOnce())->method('create')->will($this->returnValue($mockCache));

		$class = new F3::FLOW3::Reflection::ClassReflection('F3::TestPackage::BasicClass');
		$method = new F3::FLOW3::Reflection::MethodReflection('F3::TestPackage::BasicClass', 'setSomeProperty');

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);

		$policyService->matches($class, $method, 1);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesReturnsTheCorrectRolesForAGivenJoinpoint() {
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['aop']['proxyCache']['enable'] = TRUE;
		$settings['security']['policy']['aclCache']['backend'] = '';
		$settings['security']['policy']['aclCache']['backendOptions'] = array();

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$cachedPolicyArray = array(
			'F3::TestPackage::BasicClass->setSomeProperty' => array(
				'ADMINISTRATOR' => array(
					'ACCESS_GRANT'
				),
				'PRIVILEGED_CUSTOMER' => array(
					'ACCESS_GRANT'
				),
			),
		);

		$mockCache = $this->getMock('F3::FLOW3::Cache::AbstractCache', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('has')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue(TRUE));
		$mockCache->expects($this->atLeastOnce())->method('get')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue($cachedPolicyArray));

		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array('create'), array(), '', FALSE);
		$mockCacheFactory->expects($this->atLeastOnce())->method('create')->will($this->returnValue($mockCache));

		$mockJoinPoint = $this->getMock('F3::FLOW3::AOP::JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getClassName')->will($this->returnValue('F3::TestPackage::BasicClass'));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodName')->will($this->returnValue('setSomeProperty'));

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);

		$expectedRolesStringRepresentations = array('ADMINISTRATOR', 'PRIVILEGED_CUSTOMER');
		$resultRoles = $policyService->getRoles($mockJoinPoint);

		$this->assertEquals(count($expectedRolesStringRepresentations), count($resultRoles), 'The policy service did not return the correct count of roles.');
		foreach ($resultRoles as $role) {
			$this->assertType('F3::FLOW3::Security::ACL::Role', $role, 'The policy service did not return role objects as expected');
			$this->assertContains((string)$role, $expectedRolesStringRepresentations, 'The policy service did not return the expected roles for the given joinpoint');
		}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesThrowsAnExceptionIfTheGivenJoinPointIsNotRegisteredInThePolicy() {
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['aop']['proxyCache']['enable'] = TRUE;
		$settings['security']['policy']['aclCache']['backend'] = '';
		$settings['security']['policy']['aclCache']['backendOptions'] = array();

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$cachedPolicyArray = array(
			'F3::TestPackage::BasicClass->setSomeProperty' => array(
				'ADMINISTRATOR' => array(
					'ACCESS_GRANT'
				),
				'PRIVILEGED_CUSTOMER' => array(
					'ACCESS_GRANT'
				),
			),
		);

		$mockCache = $this->getMock('F3::FLOW3::Cache::AbstractCache', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('has')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue(TRUE));
		$mockCache->expects($this->atLeastOnce())->method('get')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue($cachedPolicyArray));

		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array('create'), array(), '', FALSE);
		$mockCacheFactory->expects($this->atLeastOnce())->method('create')->will($this->returnValue($mockCache));

		$mockJoinPoint = $this->getMock('F3::FLOW3::AOP::JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getClassName')->will($this->returnValue('F3::TestPackage::BasicClass'));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodName')->will($this->returnValue('notExistantMethod'));

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);

		try {
			$resultRoles = $policyService->getRoles($mockJoinPoint);
			$this->fail('getRoles() did not throw an exception.');
		} catch (F3::FLOW3::Security::Exception::NoEntryInPolicy $exception) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilgesReturnsTheCorrectPrivilegesForAGivenJoinpoint() {
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['aop']['proxyCache']['enable'] = TRUE;
		$settings['security']['policy']['aclCache']['backend'] = '';
		$settings['security']['policy']['aclCache']['backendOptions'] = array();
		$settings['security']['policy']['roles'] = array(
			'ADMINISTRATOR' => array(),
			'CUSTOMER' => array(),
			'PRIVILEGED_CUSTOMER' => array('CUSTOMER'),
		);

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$cachedPolicyArray = array(
			'F3::TestPackage::BasicClass->setSomeProperty' => array(
				'ADMINISTRATOR' => array(
					'ACCESS_GRANT'
				),
				'CUSTOMER' => array(
					'ACCESS_GRANT',
					'CUSTOMPRIVILEGE_GRANT'
				),
				'PRIVILEGED_CUSTOMER' => array(
					'CUSTOMPRIVILEGE_DENY'
				),
			),
		);

		$mockCache = $this->getMock('F3::FLOW3::Cache::AbstractCache', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('has')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue(TRUE));
		$mockCache->expects($this->atLeastOnce())->method('get')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue($cachedPolicyArray));

		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array('create'), array(), '', FALSE);
		$mockCacheFactory->expects($this->atLeastOnce())->method('create')->will($this->returnValue($mockCache));

		$mockJoinPoint = $this->getMock('F3::FLOW3::AOP::JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getClassName')->will($this->returnValue('F3::TestPackage::BasicClass'));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodName')->will($this->returnValue('setSomeProperty'));

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);

		$expectedPrivilegesStringRepresentation = array('ACCESS', 'CUSTOMPRIVILEGE');
		$resultPrivileges = $policyService->getPrivileges(new F3::FLOW3::Security::ACL::Role('PRIVILEGED_CUSTOMER'), $mockJoinPoint);

		$this->assertEquals(count($expectedPrivilegesStringRepresentation), count($resultPrivileges), 'The policy service did not return the correct count of privileges.');
		foreach ($resultPrivileges as $privilege) {
			$this->assertType('F3::FLOW3::Security::ACL::Privilege', $privilege, 'The policy service did not return privilege objects as expected');
			if ((string)$privilege === 'ACCESS') $this->assertTrue($privilege->isGrant(), 'The access privilege was not set granting as expected');
			elseif ((string)$privilege === 'CUSTOMPRIVILEGE') $this->assertTrue($privilege->isDeny(), 'The customprivilege privilege was not set denying as expected');
			else $this->fail('Unexpected privilege type found: ' . (string)$privilege);
		}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilgesReturnsTheCorrectPrivilegeForAGivenJoinpointAndPrivilegeType() {
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['aop']['proxyCache']['enable'] = TRUE;
		$settings['security']['policy']['aclCache']['backend'] = '';
		$settings['security']['policy']['aclCache']['backendOptions'] = array();
		$settings['security']['policy']['roles'] = array(
			'ADMINISTRATOR' => array(),
			'CUSTOMER' => array(),
			'PRIVILEGED_CUSTOMER' => array('CUSTOMER'),
		);

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$cachedPolicyArray = array(
			'F3::TestPackage::BasicClass->setSomeProperty' => array(
				'ADMINISTRATOR' => array(
					'ACCESS_GRANT'
				),
				'CUSTOMER' => array(
					'ACCESS_GRANT',
					'CUSTOMPRIVILEGE_GRANT'
				),
				'PRIVILEGED_CUSTOMER' => array(
					'CUSTOMPRIVILEGE_DENY'
				),
			),
		);

		$mockCache = $this->getMock('F3::FLOW3::Cache::AbstractCache', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('has')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue(TRUE));
		$mockCache->expects($this->atLeastOnce())->method('get')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue($cachedPolicyArray));

		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array('create'), array(), '', FALSE);
		$mockCacheFactory->expects($this->atLeastOnce())->method('create')->will($this->returnValue($mockCache));

		$mockJoinPoint = $this->getMock('F3::FLOW3::AOP::JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getClassName')->will($this->returnValue('F3::TestPackage::BasicClass'));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodName')->will($this->returnValue('setSomeProperty'));

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);

		$resultPrivilege = $policyService->getPrivileges(new F3::FLOW3::Security::ACL::Role('PRIVILEGED_CUSTOMER'), $mockJoinPoint, 'ACCESS');

		$this->assertType('F3::FLOW3::Security::ACL::Privilege', $resultPrivilege[0], 'The policy service did not return a privilege object as expected');
		$this->assertEquals((string)$resultPrivilege[0], 'ACCESS', 'The wrong privilege type was returned.');
		$this->assertTrue($resultPrivilege[0]->isGrant(), 'The privilege was not set granting as expected');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesThrowsAnExceptionIfTheGivenJoinPointIsNotRegisteredInThePolicy() {
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['aop']['proxyCache']['enable'] = TRUE;
		$settings['security']['policy']['aclCache']['backend'] = '';
		$settings['security']['policy']['aclCache']['backendOptions'] = array();

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$cachedPolicyArray = array(
			'F3::TestPackage::BasicClass->setSomeProperty' => array(
				'ADMINISTRATOR' => array(
					'ACCESS_GRANT'
				),
				'PRIVILEGED_CUSTOMER' => array(
					'ACCESS_GRANT'
				),
			),
		);

		$mockCache = $this->getMock('F3::FLOW3::Cache::AbstractCache', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('has')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue(TRUE));
		$mockCache->expects($this->atLeastOnce())->method('get')->with('FLOW3_Security_Policy_ACLs')->will($this->returnValue($cachedPolicyArray));

		$mockCacheFactory = $this->getMock('F3::FLOW3::Cache::Factory', array('create'), array(), '', FALSE);
		$mockCacheFactory->expects($this->atLeastOnce())->method('create')->will($this->returnValue($mockCache));

		$mockJoinPoint = $this->getMock('F3::FLOW3::AOP::JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getClassName')->will($this->returnValue('F3::TestPackage::BasicClass'));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodName')->will($this->returnValue('notExistantMethod'));

		$mockRole = $this->getMock('F3::FLOW3::Security::ACL::Role', array(), array(), '', FALSE);

		$policyService = new F3::FLOW3::Security::ACL::PolicyService($this->componentManager, $mockConfigurationManager, $mockCacheFactory);

		try {
			$resultRoles = $policyService->getPrivileges($mockRole, $mockJoinPoint);
			$this->fail('getPrivielges() did not throw an exception.');
		} catch (F3::FLOW3::Security::Exception::NoEntryInPolicy $exception) {}
	}
}
?>