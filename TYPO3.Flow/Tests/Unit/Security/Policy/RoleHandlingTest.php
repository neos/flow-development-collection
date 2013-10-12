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
 * Testcase for role management
 */
class RoleHandlingTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	public function setUp() {
		$package1 = $this->getMock('TYPO3\Flow\Package\PackageInterface');
		$package1->expects($this->any())->method('getPackageKey')->will($this->returnValue('Foo.Package1'));
		$package1->expects($this->any())->method('getConfigurationPath')->will($this->returnValue(__DIR__ . '/../Fixture/Roles/Foo.Package1/'));

		$package2 = $this->getMock('TYPO3\Flow\Package\PackageInterface');
		$package2->expects($this->any())->method('getPackageKey')->will($this->returnValue('Package2'));
		$package2->expects($this->any())->method('getConfigurationPath')->will($this->returnValue(__DIR__ . '/../Fixture/Roles/Package2/'));

		$this->configurationManager = new \TYPO3\Flow\Configuration\ConfigurationManager(new \TYPO3\Flow\Core\ApplicationContext('Testing'));
		$this->configurationManager->setPackages(array(
			$package1->getPackageKey() => $package1,
			$package2->getPackageKey() => $package2
		));
		$this->configurationManager->injectConfigurationSource(new \TYPO3\Flow\Configuration\Source\YamlSource());

		$this->policyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('setAclsForEverybodyRole'), array(), '', FALSE);
		$this->policyService->injectConfigurationManager($this->configurationManager);
		$this->policyService->_set('policy', $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY));
	}

	/**
	 * @test
	 */
	public function packageKeysAreAddedToRoleConfiguration() {
		$configuration = $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY);

		$this->assertEquals(
			array(
				'Foo.Package1:Administrator',
				'Package2:User',
				'Package2:PowerUser'
			),
			array_keys($configuration['roles'])
		);
	}

	/**
	 * @test
	 */
	public function packageKeysAreAddedToAclsConfiguration() {
		$configuration = $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY);

		$this->assertEquals(
			array(
				'Foo.Package1:Administrator' => array(
					'methods' => array(
						'method1' => 'Some policy from Foo.Package1 for Foo.Package1:Administrator',
						'method2' => 'Some policy from Package2 for Foo.Package1:Administrator'
					)
				),
				'Package2:User' => array(
					'methods' => array(
						'method1' => 'Some policy from Foo.Package1 for Package2:User with packageKey definition',
						'method2' => 'Some policy from Package2 for Package2:User wit PackageKey',
						'method3' => 'Some policy from Package2 for Package2:User without PackageKey'
					)
				),
				'Package2:PowerUser' => array(
					'methods' => array(
						'method1' => 'Some policy from Package2 for Package2:PowerUser'
					)
				)
			),
			$configuration['acls']
		);
	}

	/**
	 * @test
	 */
	public function packageKeysAreAddedToParentRoles() {
		$configuration = $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY);

		$this->assertEquals(array('Package2:PowerUser'), $configuration['roles']['Foo.Package1:Administrator']);
		$this->assertEquals(array('Package2:User'), $configuration['roles']['Package2:PowerUser']);
	}

}
