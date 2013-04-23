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

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Policy\Role;

/**
 * Testcase for for the policy service
 */
class RoleTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * data provider
	 *
	 * @return array
	 */
	public function roleIdentifiersAndPackageKeysAndNames() {
		return array(
			array('Everybody', 'Everybody', NULL),
			array('Acme.Demo:Test', 'Test', 'Acme.Demo'),
			array('Acme.Demo.Sub:Test', 'Test', 'Acme.Demo.Sub')
		);
	}

	/**
	 * @dataProvider roleIdentifiersAndPackageKeysAndNames
	 * @test
	 */
	public function setNameAndPackageKeyWorks($roleIdentifier, $name, $packageKey) {
		$role = new Role($roleIdentifier);
		$role->initializeObject();

		$this->assertEquals($name, $role->getName());
		$this->assertEquals($packageKey, $role->getPackageKey());
	}

	/**
	 * @test
	 */
	public function setParentRolesMakesSureThatParentRolesDontContainDuplicates() {
		$role = new Role('Acme.Demo:Test');
		$role->initializeObject();

		$parentRole1 = new Role('Acme.Demo:Parent1');
		$parentRole2 = new Role('Acme.Demo:Parent2');

		$parentRole2->addParentRole($parentRole1);

		$role->setParentRoles(array($parentRole1, $parentRole2, $parentRole2, $parentRole1));

		$expectedParentRoles = array(
			'Acme.Demo:Parent1' => $parentRole1,
			'Acme.Demo:Parent2' => $parentRole2
		);

			// Internally, parentRoles might contain duplicates which Doctrine will try
			// to persist - even though getParentRoles() will return an array which
			// does not contain duplicates:
		$internalParentRolesCollection = ObjectAccess::getProperty($role, 'parentRoles', TRUE);
		$this->assertEquals(2, count($internalParentRolesCollection->toArray()));

		$this->assertEquals($expectedParentRoles, $role->getParentRoles());
	}

}
?>
