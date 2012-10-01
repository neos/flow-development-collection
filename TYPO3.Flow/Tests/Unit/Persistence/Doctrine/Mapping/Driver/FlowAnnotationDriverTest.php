<?php
namespace TYPO3\FLOW3\Tests\Unit\Persistence\Doctrine\Mapping\Driver;

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
 * Testcase for the FLOW3 annotation driver
 *
 */
class Flow3AnnotationDriverTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * Data provider for testInferTableNameFromClassName
	 *
	 * @return array
	 */
	public function classNameToTableNameMappings() {
		return array(
			array('TYPO3\Party\Domain\Model\Person', 'typo3_party_domain_model_person'),
			array('SomePackage\Domain\Model\Blob', 'somepackage_domain_model_blob'),
			array('TYPO3\FLOW3\Security\Policy\Role', 'typo3_flow3_security_policy_role'),
			array('TYPO3\FLOW3\Security\Account', 'typo3_flow3_security_account'),
			array('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', 'typo3_flow3_security_authorization_resource_securitypublis_6180a')
		);
	}

	/**
	 * @test
	 * @dataProvider classNameToTableNameMappings
	 */
	public function testInferTableNameFromClassName($className, $tableName) {
		$driver = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver', array('getMaxIdentifierLength'));
		$driver->expects($this->any())->method('getMaxIdentifierLength')->will($this->returnValue(64));
		$this->assertEquals($tableName, $driver->inferTableNameFromClassName($className));
	}

	/**
	 * Data provider for testInferJoinTableNameFromClassAndPropertyName
	 *
	 * @return array
	 */
	public function classAndPropertyNameToJoinTableNameMappings() {
		return array(
			array('TYPO3\Party\Domain\Model\Person', 'propertyName', 'typo3_party_domain_model_person_propertyname_join'),
			array('SomePackage\Domain\Model\Blob', 'propertyName', 'somepackage_domain_model_blob_propertyname_join'),
			array('TYPO3\FLOW3\Security\Policy\Role', 'propertyName', 'typo3_flow3_security_policy_role_propertyname_join'),
			array('TYPO3\FLOW3\Security\Account', 'propertyName', 'typo3_flow3_security_account_propertyname_join'),
			array('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', 'propertyName', 'typo3_flow3_security_authorization_resou_6180a_propertyname_join')
		);
	}

	/**
	 * @test
	 * @dataProvider classAndPropertyNameToJoinTableNameMappings
	 */
	public function testInferJoinTableNameFromClassAndPropertyName($className, $propertyName, $tableName) {
		$driver = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver', array('getMaxIdentifierLength'));
		$driver->expects($this->any())->method('getMaxIdentifierLength')->will($this->returnValue(64));
		$this->assertEquals($tableName, $driver->_call('inferJoinTableNameFromClassAndPropertyName', $className, $propertyName));
	}

	/**
	 * @test
	 */
	public function getMaxIdentifierLengthAsksDoctrineForValue() {
		$mockDatabasePlatform = $this->getMockForAbstractClass('Doctrine\DBAL\Platforms\AbstractPlatform', array(), '', TRUE, TRUE, TRUE, array('getMaxIdentifierLength'));
		$mockDatabasePlatform->expects($this->atLeastOnce())->method('getMaxIdentifierLength')->will($this->returnValue(2048));
		$mockConnection = $this->getMock('Doctrine\DBAL\Connection', array(), array(), '', FALSE);
		$mockConnection->expects($this->atLeastOnce())->method('getDatabasePlatform')->will($this->returnValue($mockDatabasePlatform));
		$mockEntityManager = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', FALSE);
		$mockEntityManager->expects($this->atLeastOnce())->method('getConnection')->will($this->returnValue($mockConnection));

		$driver = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver', array('dummy'));
		$driver->_set('entityManager', $mockEntityManager);
		$this->assertEquals(2048, $driver->_call('getMaxIdentifierLength'));
	}

}
?>