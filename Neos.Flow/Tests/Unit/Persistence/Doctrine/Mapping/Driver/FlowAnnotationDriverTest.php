<?php
declare(strict_types=1);

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
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

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
    public function classNameToTableNameMappings(): array
    {
        return [
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
    public function testInferTableNameFromClassName($className, $tableName): void
    {
        /** @var FlowAnnotationDriver|MockObject $driver */
        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['getMaxIdentifierLength']);
        $driver->expects(self::any())->method('getMaxIdentifierLength')->willReturn(64);
        self::assertEquals($tableName, $driver->inferTableNameFromClassName($className));
    }

    /**
     * Data provider for testInferJoinTableNameFromClassAndPropertyName
     *
     * @return array
     */
    public function classAndPropertyNameToJoinTableNameMappings(): array
    {
        return [
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
    public function testInferJoinTableNameFromClassAndPropertyName($maxIdentifierLength, $className, $propertyName, $expectedTableName): void
    {
        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['getMaxIdentifierLength']);
        $driver->method('getMaxIdentifierLength')->willReturn($maxIdentifierLength);

        /** @noinspection PhpUndefinedMethodInspection */
        $actualTableName = $driver->_call('inferJoinTableNameFromClassAndPropertyName', $className, $propertyName);
        self::assertEquals($expectedTableName, $actualTableName);
        self::assertTrue(strlen($actualTableName) <= $maxIdentifierLength);
    }

    /**
     * @test
     */
    public function getMaxIdentifierLengthAsksDoctrineForValue(): void
    {
        $mockDatabasePlatform = $this->getMockForAbstractClass(AbstractPlatform::class, [], '', true, true, true, ['getMaxIdentifierLength']);
        $mockDatabasePlatform->expects(self::atLeastOnce())->method('getMaxIdentifierLength')->willReturn(2048);
        $mockConnection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $mockConnection->expects(self::atLeastOnce())->method('getDatabasePlatform')->willReturn($mockDatabasePlatform);
        $mockEntityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $mockEntityManager->expects(self::atLeastOnce())->method('getConnection')->willReturn($mockConnection);

        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['dummy']);
        /** @noinspection PhpUndefinedMethodInspection */
        $driver->_set('entityManager', $mockEntityManager);
        /** @noinspection PhpUndefinedMethodInspection */
        self::assertEquals(2048, $driver->_call('getMaxIdentifierLength'));
    }
}
