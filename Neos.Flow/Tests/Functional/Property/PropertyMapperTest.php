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

use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\ObjectConverter;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Account;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Tests\Functional\Property\Fixtures;

/**
 * Testcase for Property Mapper
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
    public function setUp()
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
        $this->assertSame('Robert Skaarhoj', $result->getName());
        $this->assertSame(25, $result->getAge());
        $this->assertSame(1.5, $result->getAverageNumberOfKids());
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
        $this->assertSame('Robert Skaarhoj', $result->getName());
        $this->assertSame(25, $result->getAge());
        $this->assertSame(1.5, $result->getAverageNumberOfKids());
    }

    /**
     * @test
     */
    public function simpleObjectWithSimplePropertiesCanBeCreated()
    {
        $source = [
            'name' => 'Christopher',
            'size' => '187',
            'signedCla' => true
        ];

        $result = $this->propertyMapper->convert($source, Fixtures\TestClass::class);
        $this->assertSame('Christopher', $result->getName());
        $this->assertSame(187, $result->getSize());
        $this->assertSame(true, $result->getSignedCla());
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
        $this->assertSame('Christopher', $result->getName());
        $this->assertSame(28, $result->getAge());
    }

    /**
     * @test
     */
    public function embeddedValueobjectCanBeMapped()
    {
        $source = array(
            'name' => 'Christopher',
            'age' => '28'
        );

        $result = $this->propertyMapper->convert($source, \Neos\Flow\Tests\Functional\Property\Fixtures\TestEmbeddedValueobject::class);
        $this->assertSame('Christopher', $result->getName());
        $this->assertSame(28, $result->getAge());
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
        $this->assertSame('42', $result->getName());
        $this->assertSame(23, $result->getSize());
    }

    /**
     * @test
     */
    public function targetTypeForEntityCanBeOverridenIfConfigured()
    {
        $source = [
            '__type' => Fixtures\TestEntitySubclass::class,
            'name' => 'Arthur',
            'age' => '42'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $result = $this->propertyMapper->convert($source, Fixtures\TestEntity::class, $configuration);
        $this->assertInstanceOf(Fixtures\TestEntitySubclass::class, $result);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception
     */
    public function overridenTargetTypeForEntityMustBeASubclass()
    {
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
    public function targetTypeForSimpleObjectCanBeOverridenIfConfigured()
    {
        $source = [
            '__type' => Fixtures\TestSubclass::class,
            'name' => 'Tower of Pisa'
        ];

        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(ObjectConverter::class, ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);

        $result = $this->propertyMapper->convert($source, Fixtures\TestClass::class, $configuration);
        $this->assertInstanceOf(Fixtures\TestSubclass::class, $result);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception
     */
    public function overridenTargetTypeForSimpleObjectMustBeASubclass()
    {
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
        $this->assertSame('Egon Olsen', $result->getName());
        $this->assertSame(42, $result->getAge());
        $this->assertSame(5.5, $result->getAverageNumberOfKids());
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
        $this->assertSame('Egon Olsen', $result->getName());
        $this->assertSame(42, $result->getAge());
        $this->assertSame(null, $result->getAverageNumberOfKids());
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
        $this->assertSame($relatedEntity, $result->getRelatedEntity());
    }

    /**
     * Testcase for http://forge.typo3.org/issues/36988 - needed for Neos
     * editing
     *
     * @test
     */
    public function ifTargetObjectTypeIsPassedAsArgumentDoNotConvertIt()
    {
        $entity = new Fixtures\TestEntity();
        $entity->setName('Egon Olsen');

        $result = $this->propertyMapper->convert($entity, Fixtures\TestEntity::class);
        $this->assertSame($entity, $result);
    }

    /**
     * Testcase for http://forge.typo3.org/issues/39445
     *
     * @test
     */
    public function ifTargetObjectTypeIsPassedRecursivelyDoNotConvertIt()
    {
        $entity = new Fixtures\TestEntity();
        $entity->setName('Egon Olsen');

        $result = $this->propertyMapper->convert([$entity], 'array<Neos\Flow\Tests\Functional\Property\Fixtures\TestEntity>');
        $this->assertSame([$entity], $result);
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
     * Testcase for #32829
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
        $this->assertInstanceOf(Fixtures\TestEntitySubclassWithNewField::class, $theHorse);
    }

    /**
     * @test
     * @dataProvider invalidTypeConverterConfigurationsForOverridingTargetTypes
     * @expectedException \Neos\Flow\Property\Exception
     */
    public function mappingToFieldsFromSubclassThrowsExceptionIfTypeConverterOptionIsInvalidOrNotSet(PropertyMappingConfigurationInterface $configuration = null)
    {
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
     * @expectedException \Neos\Flow\Property\Exception
     */
    public function convertFromShouldThrowExceptionIfGivenSourceTypeIsNotATargetType()
    {
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

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(2, count($account->getRoles()));
        $this->assertEquals($expectedRoleIdentifiers, array_keys($account->getRoles()));
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

        $this->assertSame($entityIdentifier, $result);
    }

    /**
     * @test
     */
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration()
    {
        $defaultConfiguration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $this->assertTrue($defaultConfiguration->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertTrue($defaultConfiguration->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));

        $this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, \Neos\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
