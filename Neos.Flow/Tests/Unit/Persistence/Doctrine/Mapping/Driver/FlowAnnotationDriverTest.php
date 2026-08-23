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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Neos\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the Flow annotation driver
 */
final class FlowAnnotationDriverTest extends UnitTestCase
{
    /**
     * Data provider for testInferTableNameFromClassName
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function classNameToTableNameMappings(): \Iterator
    {
        yield ['SomePackage\Domain\Model\Blob', 'somepackage_domain_model_blob'];
        yield [Role::class, 'neos_flow_security_policy_role'];
        yield [Account::class, 'neos_flow_security_account'];
        yield ['Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'neos_flow_security_authorization_resource_securitypublishi_07c54'];
    }

    #[DataProvider('classNameToTableNameMappings')]
    #[Test]
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
     * @return \Iterator<(int | string), mixed>
     */
    public static function classAndPropertyNameToJoinTableNameMappings(): \Iterator
    {
        yield [64, 'SomePackage\Domain\Model\Blob', 'propertyName', 'somepackage_domain_model_blob_propertyname_join'];
        yield [64, Role::class, 'propertyName', 'neos_flow_security_policy_role_propertyname_join'];
        yield [64, Account::class, 'propertyName', 'neos_flow_security_account_propertyname_join'];
        yield [64, 'Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'propertyName', 'neos_flow_security_authorization_resourc_07c54_propertyname_join'];
        yield [30, 'Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'propertyName', 'neos_f_07c54_propertyname_join'];
        yield [30, 'Neos\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', 'somePrettyLongPropertyNameWhichMustBeShortened', 'neos_flow_security_autho_6afa5'];
    }

    #[DataProvider('classAndPropertyNameToJoinTableNameMappings')]
    #[Test]
    public function testInferJoinTableNameFromClassAndPropertyName($maxIdentifierLength, $className, $propertyName, $expectedTableName): void
    {
        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, ['getMaxIdentifierLength']);
        $driver->method('getMaxIdentifierLength')->willReturn($maxIdentifierLength);

        /** @noinspection PhpUndefinedMethodInspection */
        $actualTableName = $driver->_call('inferJoinTableNameFromClassAndPropertyName', $className, $propertyName);
        self::assertEquals($expectedTableName, $actualTableName);
        self::assertLessThanOrEqual($maxIdentifierLength, strlen($actualTableName));
    }

    #[Test]
    public function getMaxIdentifierLengthAsksDoctrineForValue(): void
    {
        $mockDatabasePlatform = $this->createPartialMock(AbstractPlatform::class, ['getMaxIdentifierLength', '_getCommonIntegerTypeDeclarationSQL', 'getBooleanTypeDeclarationSQL', 'getIntegerTypeDeclarationSQL', 'getBigIntTypeDeclarationSQL', 'getSmallIntTypeDeclarationSQL', 'initializeDoctrineTypeMappings', 'getClobTypeDeclarationSQL', 'getBlobTypeDeclarationSQL', 'getName', 'getCurrentDatabaseExpression']);

        $mockDatabasePlatform->expects(self::atLeastOnce())->method('getMaxIdentifierLength')->willReturn(2048);
        $mockConnection = $this->createMock(Connection::class);
        $mockConnection->expects(self::atLeastOnce())->method('getDatabasePlatform')->willReturn($mockDatabasePlatform);
        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockEntityManager->expects(self::atLeastOnce())->method('getConnection')->willReturn($mockConnection);

        $driver = $this->getAccessibleMock(FlowAnnotationDriver::class, []);
        /** @noinspection PhpUndefinedMethodInspection */
        $driver->_set('entityManager', $mockEntityManager);
        /** @noinspection PhpUndefinedMethodInspection */
        self::assertEquals(2048, $driver->_call('getMaxIdentifierLength'));
    }
}
