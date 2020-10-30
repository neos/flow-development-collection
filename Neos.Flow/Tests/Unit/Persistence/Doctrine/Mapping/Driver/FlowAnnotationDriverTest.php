<?php
namespace Neos\Flow\Tests\Unit\Persistence\Doctrine\Mapping\Driver;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security;

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
            [\Neos\Party\Domain\Model\Person::class, 'neos_party_domain_model_person'],
            ['SomePackage\Domain\Model\Blob', 'somepackage_domain_model_blob'],
            [Security\Policy\Role::class, 'neos_flow_security_policy_role'],
            [Security\Account::class, 'neos_flow_security_account'],
            ['Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'neos_flow_security_authorization_resource_securitypublishi_07c54']
        ];
    }

    /**
     * @test
     * @dataProvider classNameToTableNameMappings
     */
    public function testInferTableNameFromClassName($className, $tableName)
    {
        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['getMaxIdentifierLength']);
        $driver->expects(self::any())->method('getMaxIdentifierLength')->will(self::returnValue(64));
        self::assertEquals($tableName, $driver->inferTableNameFromClassName($className));
    }

    /**
     * Data provider for testInferJoinTableNameFromClassAndPropertyName
     *
     * @return array
     */
    public function classAndPropertyNameToJoinTableNameMappings()
    {
        return [
            [64, \Neos\Party\Domain\Model\Person::class, 'propertyName', 'neos_party_domain_model_person_propertyname_join'],
            [64, 'SomePackage\Domain\Model\Blob', 'propertyName', 'somepackage_domain_model_blob_propertyname_join'],
            [64, Security\Policy\Role::class, 'propertyName', 'neos_flow_security_policy_role_propertyname_join'],
            [64, Security\Account::class, 'propertyName', 'neos_flow_security_account_propertyname_join'],
            [64, 'Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'propertyName', 'neos_flow_security_authorization_resourc_07c54_propertyname_join'],
            [30, 'Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'propertyName', 'neos_f_07c54_propertyname_join'],
            [30, 'Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'somePrettyLongPropertyNameWhichMustBeShortened', 'neos_flow_security_autho_6afa5']
        ];
    }

    /**
     * @test
     * @dataProvider classAndPropertyNameToJoinTableNameMappings
     */
    public function testInferJoinTableNameFromClassAndPropertyName($maxIdentifierLength, $className, $propertyName, $expectedTableName)
    {
        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['getMaxIdentifierLength']);
        $driver->expects(self::any())->method('getMaxIdentifierLength')->will(self::returnValue($maxIdentifierLength));

        $actualTableName = $driver->_call('inferJoinTableNameFromClassAndPropertyName', $className, $propertyName);
        self::assertEquals($expectedTableName, $actualTableName);
        self::assertTrue(strlen($actualTableName) <= $maxIdentifierLength);
    }

    /**
     * @test
     */
    public function getMaxIdentifierLengthAsksDoctrineForValue()
    {
        $mockDatabasePlatform = $this->getMockForAbstractClass('Doctrine\DBAL\Platforms\AbstractPlatform', [], '', true, true, true, ['getMaxIdentifierLength']);
        $mockDatabasePlatform->expects(self::atLeastOnce())->method('getMaxIdentifierLength')->will(self::returnValue(2048));
        $mockConnection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $mockConnection->expects(self::atLeastOnce())->method('getDatabasePlatform')->will(self::returnValue($mockDatabasePlatform));
        $mockEntityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $mockEntityManager->expects(self::atLeastOnce())->method('getConnection')->will(self::returnValue($mockConnection));

        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['dummy']);
        $driver->_set('entityManager', $mockEntityManager);
        self::assertEquals(2048, $driver->_call('getMaxIdentifierLength'));
    }
}
