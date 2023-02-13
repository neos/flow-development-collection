<?php
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
use Neos\Flow\Tests\Functional\Property\Fixtures\TestClassWithMissingCollectionElementType;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Test case for Property Mapper
 */
class PropertyMapperTest extends FunctionalTestCase
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

    /**
     * @test
     */
    public function domainObjectWithSimplePropertiesCanBeCreated()
    {
        $source = [
            'name' => 'Robert Skaarhoj',
            'age' => '25',
            'averageNumberOfKids' => '1.5'
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestEntity::class);
        self::assertSame('Robert Skaarhoj', $result->getName());
        self::assertSame(25, $result->getAge());
        self::assertSame(1.5, $result->getAverageNumberOfKids());
    }

    /**
     * @test
     */
    public function domainObjectWithVirtualPropertiesCanBeCreated()
    {
        $source = [
            'name' => 'Robert Skaarhoj',
            'yearOfBirth' => '1988',
            'averageNumberOfKids' => '1.5'
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestEntity::class);
        self::assertSame('Robert Skaarhoj', $result->getName());
        self::assertSame(25, $result->getAge());
        self::assertSame(1.5, $result->getAverageNumberOfKids());
    }

    /**
     * @test
     */
    public function simpleObjectWithSimplePropertiesCanBeCreated()
    {
        $source = [
            'name' => 'Christopher',
            'size' => '187',
            'signedCla' => true,
            'signedClaBool' => true
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestClass::class);
        self::assertSame('Christopher', $result->getName());
        self::assertSame(187, $result->getSize());
        self::assertTrue($result->getSignedCla());
    }

    /**
     * @test
     */
    public function valueobjectCanBeMapped()
    {
        $source = [
            '__identity' => 'abcdefghijkl',
            'name' => 'Christopher',
            'age' => '28'
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestValueobject::class);
        self::assertSame('Christopher', $result->getName());
        self::assertSame(28, $result->getAge());
    }

    /**
     * @test
     */
    public function embeddedValueobjectCanBeMapped()
    {
        $source = [
            'name' => 'Christopher',
            'age' => '28'
        ];

        $result = $this->propertyMapper->convert($source, \Neos\Flow\Tests\Functional\Property\Fixtures\TestEmbeddedValueobject::class);
        self::assertSame('Christopher', $result->getName());
        self::assertSame(28, $result->getAge());
    }

    /**
     * @test
     */
    public function integerCanBeMappedToString()
    {
        $source = [
            'name' => 42,
            'size' => 23
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestClass::class);
        self::assertSame('42', $result->getName());
        self::assertSame(23, $result->getSize());
    }

    /**
     * @test
     */
    public function targetTypeForEntityCanBeOverriddenIfConfigured()
    {
        $source = [
            '__type' => Fixtures\TestEntitySubclass::class,
            'name' => 'Arthur',
            'age' => '42'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $result = $this->propertyMapper->convert($source, Fixtures\TestEntity::class, $configuration);
        self::assertInstanceOf(Fixtures\TestEntitySubclass::class, $result);
    }

    /**
     * @test
     */
    public function overriddenTargetTypeForEntityMustBeASubclass()
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => Fixtures\TestClass::class,
            'name' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $this->propertyMapper->convert($source, Fixtures\TestEntity::class, $configuration);
    }

    /**
     * @test
     */
    public function targetTypeForSimpleObjectCanBeOverriddenIfConfigured()
    {
        $source = [
            '__type' => Fixtures\TestSubclass::class,
            'name' => 'Tower of Pisa'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(ObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $result = $this->propertyMapper->convert($source, Fixtures\TestClass::class, $configuration);
        self::assertInstanceOf(Fixtures\TestSubclass::class, $result);
    }

    /**
     * @test
     */
    public function overriddenTargetTypeForSimpleObjectMustBeASubclass()
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => Fixtures\TestEntity::class,
            'name' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(ObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $this->propertyMapper->convert($source, Fixtures\TestClass::class, $configuration);
    }

    /**
     * @test
     */
    public function mappingPersistentEntityOnlyChangesModifiedProperties()
    {
        $entityIdentity = $this->createTestEntity();

        $source = [
            '__identity' => $entityIdentity,
            'averageNumberOfKids' => '5.5'
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestEntity::class);
        self::assertSame('Egon Olsen', $result->getName());
        self::assertSame(42, $result->getAge());
        self::assertSame(5.5, $result->getAverageNumberOfKids());
    }

    /**
     * @test
     */
    public function mappingPersistentEntityAllowsToSetValueToNull()
    {
        $entityIdentity = $this->createTestEntity();

        $source = [
            '__identity' => $entityIdentity,
            'averageNumberOfKids' => ''
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestEntity::class);
        self::assertSame('Egon Olsen', $result->getName());
        self::assertSame(42, $result->getAge());
        self::assertNull($result->getAverageNumberOfKids());
    }

    /**
     * @test
     */
    public function mappingOfPropertiesWithUnqualifiedInterfaceName()
    {
        $relatedEntity = new Fixtures\TestEntity();

        $source = [
            'relatedEntity' => $relatedEntity,
        ];
        $result = $this->propertyMapper->convert($source, Fixtures\TestEntity::class);
        self::assertSame($relatedEntity, $result->getRelatedEntity());
    }

    /**
     * Test case for http://forge.typo3.org/issues/36988 - needed for Neos
     * editing
     *
     * @test
     */
    public function ifTargetObjectTypeIsPassedAsArgumentDoNotConvertIt()
    {
        $entity = new Fixtures\TestEntity();
        $entity->setName('Egon Olsen');

        $result = $this->propertyMapper->convert($entity, Fixtures\TestEntity::class);
        self::assertSame($entity, $result);
    }

    /**
     * Test case for http://forge.typo3.org/issues/39445
     *
     * @test
     */
    public function ifTargetObjectTypeIsPassedRecursivelyDoNotConvertIt()
    {
        $entity = new Fixtures\TestEntity();
        $entity->setName('Egon Olsen');

        $result = $this->propertyMapper->convert([$entity], 'array<Neos\Flow\Tests\Functional\Property\Fixtures\TestEntity>');
        self::assertSame([$entity], $result);
    }

    /**
     * ObjectConverter->getTypeOfChildProperty will return null if the given property is unknown and skipUnknownPropertiers()
     * is set. This test makes sure that doMapping() will skip such a property.
     *
     * @test
     */
    public function skipPropertyIfTypeConverterReturnsNullForChildPropertyType()
    {
        $source = [
            'name' => 'Smilla',
            'unknownProperty' => 'Oh Harvey!'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->skipUnknownProperties();

        $mappingResult = $this->propertyMapper->convert($source, Fixtures\TestClass::class, $configuration);
        self::assertInstanceOf(Fixtures\TestClass::class, $mappingResult);
    }

    /**
     * Add and persist a test entity, and return the identifier of the newly created
     * entity.
     *
     * @return string identifier of newly created entity
     */
    protected function createTestEntity()
    {
        $entity = new Fixtures\TestEntity();
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
     *
     * @test
     */
    public function mappingToFieldsFromSubclassWorksIfTargetTypeIsOverridden()
    {
        $source = [
            '__type' => Fixtures\TestEntitySubclassWithNewField::class,
            'testField' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $theHorse = $this->propertyMapper->convert($source, Fixtures\TestEntity::class, $configuration);
        self::assertInstanceOf(Fixtures\TestEntitySubclassWithNewField::class, $theHorse);
    }

    /**
     * @test
     * @dataProvider invalidTypeConverterConfigurationsForOverridingTargetTypes
     */
    public function mappingToFieldsFromSubclassThrowsExceptionIfTypeConverterOptionIsInvalidOrNotSet(PropertyMappingConfigurationInterface $configuration = null)
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => Fixtures\TestEntitySubclassWithNewField::class,
            'testField' => 'A horse'
        ];

        $this->propertyMapper->convert($source, Fixtures\TestEntity::class, $configuration);
    }

    /**
     * Data provider with invalid configuration for target type overrides
     *
     * @return array
     */
    public function invalidTypeConverterConfigurationsForOverridingTargetTypes()
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

    /**
     * @test
     */
    public function convertFromShouldThrowExceptionIfGivenSourceTypeIsNotATargetType()
    {
        $this->expectException(Exception::class);
        $source = [
            '__type' => Fixtures\TestClass::class,
            'testField' => 'A horse'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $this->propertyMapper->convert($source, Fixtures\TestEntity::class, $configuration);
    }

    /**
     * Test case for #47232
     *
     * @test
     */
    public function convertedAccountRolesCanBeSet()
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
        self::assertEquals($expectedRoleIdentifiers, array_keys($account->getRoles()));
    }

    /**
     * @test
     */
    public function persistentEntityCanBeSerializedToIdentifierUsingObjectSource()
    {
        $entity = new Fixtures\TestEntity();
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

    /**
     * @test
     */
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration()
    {
        $defaultConfiguration = $this->propertyMapper->buildPropertyMappingConfiguration();
        self::assertTrue($defaultConfiguration->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertTrue($defaultConfiguration->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));

        self::assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }

    /**
     * @test
     */
    public function foo()
    {
        $actualResult = $this->propertyMapper->convert(true, 'int');
        self::assertSame(42, $actualResult);
    }

    /**
     * @test
     */
    public function collectionPropertyWithMissingElementTypeThrowsHelpfulException()
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
