<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Doctrine\Mapping\Driver;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Security;
use TYPO3\Party\Domain\Model;

/**
 * Testcase for the Flow annotation driver
 */
class FlowAnnotationDriverTest extends UnitTestCase
{
    /**
     * Data provider for testInferTableNameFromClassName
     *
     * @return array
     */
    public function classNameToTableNameMappings()
    {
        return [
            [Model\Person::class, 'typo3_party_domain_model_person'],
            ['SomePackage\Domain\Model\Blob', 'somepackage_domain_model_blob'],
            [Security\Policy\Role::class, 'typo3_flow_security_policy_role'],
            [Security\Account::class, 'typo3_flow_security_account'],
            ['TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'typo3_flow_security_authorization_resource_securitypublish_861cb']
        ];
    }

    /**
     * @test
     * @dataProvider classNameToTableNameMappings
     */
    public function testInferTableNameFromClassName($className, $tableName)
    {
        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['getMaxIdentifierLength']);
        $driver->expects($this->any())->method('getMaxIdentifierLength')->will($this->returnValue(64));
        $this->assertEquals($tableName, $driver->inferTableNameFromClassName($className));
    }

    /**
     * Data provider for testInferJoinTableNameFromClassAndPropertyName
     *
     * @return array
     */
    public function classAndPropertyNameToJoinTableNameMappings()
    {
        return [
            [64, Model\Person::class, 'propertyName', 'typo3_party_domain_model_person_propertyname_join'],
            [64, 'SomePackage\Domain\Model\Blob', 'propertyName', 'somepackage_domain_model_blob_propertyname_join'],
            [64, Security\Policy\Role::class, 'propertyName', 'typo3_flow_security_policy_role_propertyname_join'],
            [64, Security\Account::class, 'propertyName', 'typo3_flow_security_account_propertyname_join'],
            [64, 'TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'propertyName', 'typo3_flow_security_authorization_resour_861cb_propertyname_join'],
            [30, 'TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'propertyName', 'typo3__861cb_propertyname_join'],
            [30, 'TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'somePrettyLongPropertyNameWhichMustBeShortened', 'typo3_flow_security_auth_6aad0']
        ];
    }

    /**
     * @test
     * @dataProvider classAndPropertyNameToJoinTableNameMappings
     */
    public function testInferJoinTableNameFromClassAndPropertyName($maxIdentifierLength, $className, $propertyName, $expectedTableName)
    {
        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['getMaxIdentifierLength']);
        $driver->expects($this->any())->method('getMaxIdentifierLength')->will($this->returnValue($maxIdentifierLength));

        $actualTableName = $driver->_call('inferJoinTableNameFromClassAndPropertyName', $className, $propertyName);
        $this->assertEquals($expectedTableName, $actualTableName);
        $this->assertTrue(strlen($actualTableName) <= $maxIdentifierLength);
    }

    /**
     * @test
     */
    public function getMaxIdentifierLengthAsksDoctrineForValue()
    {
        $mockDatabasePlatform = $this->getMockForAbstractClass('Doctrine\DBAL\Platforms\AbstractPlatform', [], '', true, true, true, ['getMaxIdentifierLength']);
        $mockDatabasePlatform->expects($this->atLeastOnce())->method('getMaxIdentifierLength')->will($this->returnValue(2048));
        $mockConnection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $mockConnection->expects($this->atLeastOnce())->method('getDatabasePlatform')->will($this->returnValue($mockDatabasePlatform));
        $mockEntityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $mockEntityManager->expects($this->atLeastOnce())->method('getConnection')->will($this->returnValue($mockConnection));

        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['dummy']);
        $driver->_set('entityManager', $mockEntityManager);
        $this->assertEquals(2048, $driver->_call('getMaxIdentifierLength'));
    }
}
