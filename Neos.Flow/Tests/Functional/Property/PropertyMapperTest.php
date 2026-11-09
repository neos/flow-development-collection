<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Property;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Property\Exception;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\ObjectConverter;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Account;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestClass;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestClassWithMissingCollectionElementType;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestEmbeddedValueobject;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestEntity;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestEntitySubclass;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestEntitySubclassWithNewField;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestSubclass;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestValueobject;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for Property Mapper
 */
final class PropertyMapperTest extends FunctionalTestCase
{
    /**
     *
     * @var PropertyMapper
     */
    protected $propertyMapper;

    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->propertyMapper = $this->objectManager->get(PropertyMapper::class);
    }

    #[Test]
    public function domainObjectWithSimplePropertiesCanBeCreated(): void
    {
        $source = [
            'name' => 'Robert Skaarhoj',
            'age' => '25',
            'averageNumberOfKids' => '1.5'
        ];

        $result = $this->propertyMapper->convert($source, TestEntity::class);
        self::assertSame('Robert Skaarhoj', $result->getName());
        self::assertSame(25, $result->getAge());
        self::assertSame(1.5, $result->getAverageNumberOfKids());
    }

    #[Test]
    public function domainObjectWithVirtualPropertiesCanBeCreated(): void
    {
        $source = [
            'name' => 'Robert Skaarhoj',
            'yearOfBirth' => '1988',
            'averageNumberOfKids' => '1.5'
        ];

        $result = $this->propertyMapper->convert($source, TestEntity::class);
        self::assertSame('Robert Skaarhoj', $result->getName());
        self::assertSame(25, $result->getAge());
        self::assertSame(1.5, $result->getAverageNumberOfKids());
    }

    #[Test]
    public function simpleObjectWithSimplePropertiesCanBeCreated(): void
    {
        $source = [
            'name' => 'Christopher',
            'size' => '187',
            'signedCla' => true,
            'signedClaBool' => true
        ];

        $result = $this->propertyMapper->convert($source, TestClass::class);
        self::assertSame('Christopher', $result->getName());
        self::assertSame(187, $result->getSize());
        self::assertTrue($result->getSignedCla());
    }

    #[Test]
    public function valueobjectCanBeMapped(): void
    {
        $source = [
            '__identity' => 'abcdefghijkl',
            'name' => 'Christopher',
            'age' => '28'
        ];

        $result = $this->propertyMapper->convert($source, TestValueobject::class);
        self::assertSame('Christopher', $result->getName());
        self::assertSame(28, $result->getAge());
    }

    #[Test]
    public function embeddedValueobjectCanBeMapped(): void
    {
        $source = [
            'name' => 'Christopher',
            'age' => '28'
        ];

        $result = $this->propertyMapper->convert($source, TestEmbeddedValueobject::class);
        self::assertSame('Christopher', $result->getName());
        self::assertSame(28, $result->getAge());
    }

    #[Test]
    public function integerCanBeMappedToString(): void
    {
        $source = [
            'name' => 42,
            'size' => 23
        ];

        $result = $this->propertyMapper->convert($source, TestClass::class);
        self::assertSame('42', $result->getName());
        self::assertSame(23, $result->getSize());
    }

    #[Test]
    public function targetTypeForEntityCanBeOverriddenIfConfigured(): void
    {
        $source = [
            '__type' => TestEntitySubclass::class,
            'name' => 'Arthur',
            'age' => '42'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $result = $this->propertyMapper->convert($source, TestEntity::class, $configuration);
        self::assertInstanceOf(TestEntitySubclass::class, $result);
    }

    #[Test]
    public function overriddenTargetTypeForEntityMustBeASubclass(): void
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => TestClass::class,
            'name' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $this->propertyMapper->convert($source, TestEntity::class, $configuration);
    }

    #[Test]
    public function targetTypeForSimpleObjectCanBeOverriddenIfConfigured(): void
    {
        $source = [
            '__type' => TestSubclass::class,
            'name' => 'Tower of Pisa'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(ObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $result = $this->propertyMapper->convert($source, TestClass::class, $configuration);
        self::assertInstanceOf(TestSubclass::class, $result);
    }

    #[Test]
    public function overriddenTargetTypeForSimpleObjectMustBeASubclass(): void
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => TestEntity::class,
            'name' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(ObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $this->propertyMapper->convert($source, TestClass::class, $configuration);
    }

    #[Test]
    public function mappingPersistentEntityOnlyChangesModifiedProperties(): void
    {
        $entityIdentity = $this->createTestEntity();

        $source = [
            '__identity' => $entityIdentity,
            'averageNumberOfKids' => '5.5'
        ];

        $result = $this->propertyMapper->convert($source, TestEntity::class);
        self::assertSame('Egon Olsen', $result->getName());
        self::assertSame(42, $result->getAge());
        self::assertSame(5.5, $result->getAverageNumberOfKids());
    }

    #[Test]
    public function mappingPersistentEntityAllowsToSetValueToNull(): void
    {
        $entityIdentity = $this->createTestEntity();

        $source = [
            '__identity' => $entityIdentity,
            'averageNumberOfKids' => ''
        ];

        $result = $this->propertyMapper->convert($source, TestEntity::class);
        self::assertSame('Egon Olsen', $result->getName());
        self::assertSame(42, $result->getAge());
        self::assertNull($result->getAverageNumberOfKids());
    }

    #[Test]
    public function mappingOfPropertiesWithUnqualifiedInterfaceName(): void
    {
        $relatedEntity = new TestEntity();

        $source = [
            'relatedEntity' => $relatedEntity,
        ];
        $result = $this->propertyMapper->convert($source, TestEntity::class);
        self::assertSame($relatedEntity, $result->getRelatedEntity());
    }

    /**
     * Test case for http://forge.typo3.org/issues/36988 - needed for Neos
     * editing
     */
    #[Test]
    public function ifTargetObjectTypeIsPassedAsArgumentDoNotConvertIt(): void
    {
        $entity = new TestEntity();
        $entity->setName('Egon Olsen');

        $result = $this->propertyMapper->convert($entity, TestEntity::class);
        self::assertSame($entity, $result);
    }

    /**
     * Test case for http://forge.typo3.org/issues/39445
     */
    #[Test]
    public function ifTargetObjectTypeIsPassedRecursivelyDoNotConvertIt(): void
    {
        $entity = new TestEntity();
        $entity->setName('Egon Olsen');

        $result = $this->propertyMapper->convert([$entity], 'array<Neos\Flow\Tests\Functional\Property\Fixtures\TestEntity>');
        self::assertSame([$entity], $result);
    }

    /**
     * ObjectConverter->getTypeOfChildProperty will return null if the given property is unknown and skipUnknownPropertiers()
     * is set. This test makes sure that doMapping() will skip such a property.
     */
    #[Test]
    public function skipPropertyIfTypeConverterReturnsNullForChildPropertyType(): void
    {
        $source = [
            'name' => 'Smilla',
            'unknownProperty' => 'Oh Harvey!'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->skipUnknownProperties();

        $mappingResult = $this->propertyMapper->convert($source, TestClass::class, $configuration);
        self::assertInstanceOf(TestClass::class, $mappingResult);
    }

    /**
     * Add and persist a test entity, and return the identifier of the newly created
     * entity.
     *
     * @return string identifier of newly created entity
     */
    protected function createTestEntity(): string
    {
        $entity = new TestEntity();
        $entity->setName('Egon Olsen');
        $entity->setAge(42);
        $entity->setAverageNumberOfKids(3.5);
        $this->persistenceManager->add($entity);
        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        return $entityIdentifier;
    }

    /**
     * Test case for #32829
     */
    #[Test]
    public function mappingToFieldsFromSubclassWorksIfTargetTypeIsOverridden(): void
    {
        $source = [
            '__type' => TestEntitySubclassWithNewField::class,
            'testField' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $theHorse = $this->propertyMapper->convert($source, TestEntity::class, $configuration);
        self::assertInstanceOf(TestEntitySubclassWithNewField::class, $theHorse);
    }

    #[DataProvider('invalidTypeConverterConfigurationsForOverridingTargetTypes')]
    #[Test]
    public function mappingToFieldsFromSubclassThrowsExceptionIfTypeConverterOptionIsInvalidOrNotSet(PropertyMappingConfigurationInterface $configuration = null): void
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => TestEntitySubclassWithNewField::class,
            'testField' => 'A horse'
        ];

        $this->propertyMapper->convert($source, TestEntity::class, $configuration);
    }

    /**
     * Data provider with invalid configuration for target type overrides
     *
     * @return array
     */
    public static function invalidTypeConverterConfigurationsForOverridingTargetTypes(): array
    {
        $configurationWithNoSetting = new PropertyMappingConfiguration();

        $configurationWithOverrideOff = new PropertyMappingConfiguration();
        $configurationWithOverrideOff->setTypeConverterOption(ObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, false);

        return [
            [null],
            [$configurationWithNoSetting],
            [$configurationWithOverrideOff],
        ];
    }

    #[Test]
    public function convertFromShouldThrowExceptionIfGivenSourceTypeIsNotATargetType(): void
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => TestClass::class,
            'testField' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $this->propertyMapper->convert($source, TestEntity::class, $configuration);
    }

    /**
     * Test case for #47232
     */
    #[Test]
    public function convertedAccountRolesCanBeSet(): void
    {
        $source = [
            'accountIdentifier' => 'someAccountIdentifier',
            'credentialsSource' => 'someEncryptedStuff',
            'authenticationProviderName' => 'DefaultProvider',
            'roles' => ['Neos.Flow:Customer', 'Neos.Flow:Administrator']
        ];

        $expectedRoleIdentifiers = ['Neos.Flow:Customer', 'Neos.Flow:Administrator'];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->forProperty('roles.*')->allowProperties();

        $account = $this->propertyMapper->convert($source, Account::class, $configuration);

        self::assertInstanceOf(Account::class, $account);
        self::assertCount(2, $account->getRoles());
        self::assertSame($expectedRoleIdentifiers, array_keys($account->getRoles()));
    }

    #[Test]
    public function persistentEntityCanBeSerializedToIdentifierUsingObjectSource(): void
    {
        $entity = new TestEntity();
        $entity->setName('Egon Olsen');
        $entity->setAge(42);
        $entity->setAverageNumberOfKids(3.5);
        $this->persistenceManager->add($entity);

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $source = $entity;

        $result = $this->propertyMapper->convert($source, 'string');

        self::assertSame($entityIdentifier, $result);
    }

    #[Test]
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration(): void
    {
        $defaultConfiguration = $this->propertyMapper->buildPropertyMappingConfiguration();
        self::assertTrue($defaultConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertTrue($defaultConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));

        self::assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }

    #[Test]
    public function foo(): void
    {
        $actualResult = $this->propertyMapper->convert(true, 'int');
        self::assertSame(42, $actualResult);
    }

    #[Test]
    public function collectionPropertyWithMissingElementTypeThrowsHelpfulException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/The annotated collection property "0" is missing an element type/');
        $source = [
            'values' => ['foo']
        ];
        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->forProperty('values.*')->allowProperties();
        $this->propertyMapper->convert($source, TestClassWithMissingCollectionElementType::class, $configuration);
    }
}
