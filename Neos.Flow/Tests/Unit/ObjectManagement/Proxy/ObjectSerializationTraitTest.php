<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ObjectManagement\Proxy\RelatedEntitiesContainer;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassUsingObjectSerializationTrait;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\SomeEntity;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\SomeEntityDoctrineProxy;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\SomeImplementation;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

require_once(__DIR__ . '/../Fixture/ClassUsingObjectSerializationTrait.php');
require_once(__DIR__ . '/../Fixture/SomeEntity.php');
require_once(__DIR__ . '/../Fixture/SomeEntityDoctrineProxy.php');
require_once(__DIR__ . '/../Fixture/SomeInterface.php');
require_once(__DIR__ . '/../Fixture/SomeImplementation.php');

/**
 * Test cases for the ObjectSerializationTrait
 *
 * The trait relies on Bootstrap::$staticObjectManager, which is replaced by a mock for the
 * duration of each test and restored afterwards.
 */
class ObjectSerializationTraitTest extends UnitTestCase
{
    protected ObjectManagerInterface|MockObject $mockObjectManager;
    protected PersistenceManagerInterface|MockObject $mockPersistenceManager;

    /**
     * The object manager which was set before this test case replaced it
     */
    protected ?ObjectManagerInterface $originalStaticObjectManager;

    /**
     * Scopes returned by the mocked object manager, indexed by object name
     * @var array<string, int>
     */
    protected array $scopes = [];

    /**
     * Object names returned by the mocked object manager, indexed by class name
     * @var array<string, string>
     */
    protected array $objectNamesByClassName = [];

    /**
     * Object names for which the mocked object manager reports that they are not registered
     * @var string[]
     */
    protected array $unregisteredObjectNames = [];

    protected ClassUsingObjectSerializationTrait $subject;

    protected function setUp(): void
    {
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('get')->with(PersistenceManagerInterface::class)->willReturn($this->mockPersistenceManager);
        $this->mockObjectManager->method('isRegistered')->willReturnCallback(
            fn ($objectName) => !in_array($objectName, $this->unregisteredObjectNames, true)
        );
        $this->mockObjectManager->method('getObjectNameByClassName')->willReturnCallback(
            fn ($className) => $this->objectNamesByClassName[$className] ?? $className
        );
        $this->mockObjectManager->method('getScope')->willReturnCallback(
            fn ($objectName) => $this->scopes[$objectName] ?? Configuration::SCOPE_PROTOTYPE
        );

        $this->originalStaticObjectManager = Bootstrap::$staticObjectManager;
        Bootstrap::$staticObjectManager = $this->mockObjectManager;

        $this->subject = new ClassUsingObjectSerializationTrait();
    }

    protected function tearDown(): void
    {
        Bootstrap::$staticObjectManager = $this->originalStaticObjectManager;
    }

    /**
     * Prepares the persistence manager mock to report the given object as an already persisted entity
     */
    protected function letPersistenceManagerKnowEntity(object $entity, string $identifier): void
    {
        $this->mockPersistenceManager->method('isNewObject')->willReturn(false);
        $this->mockPersistenceManager->method('getIdentifierByObject')->with($entity)->willReturn($identifier);
    }

    #[Test]
    public function ordinaryPropertiesAreSerialized(): void
    {
        $propertiesToSerialize = $this->subject->serializeRelatedEntities();

        self::assertContains('simpleProperty', $propertiesToSerialize);
        self::assertContains('arrayProperty', $propertiesToSerialize);
    }

    #[Test]
    public function staticPropertiesAreNotSerialized(): void
    {
        self::assertNotContains('staticProperty', $this->subject->serializeRelatedEntities());
    }

    #[Test]
    public function transientPropertiesAreNotSerialized(): void
    {
        $propertiesToSerialize = $this->subject->serializeRelatedEntities(['transientProperty']);

        self::assertNotContains('transientProperty', $propertiesToSerialize);
        self::assertContains('simpleProperty', $propertiesToSerialize);
    }

    #[Test]
    public function injectedPropertiesAreNotSerialized(): void
    {
        self::assertNotContains('injectedProperty', $this->subject->serializeRelatedEntities());
    }

    #[Test]
    public function theInternalBookkeepingPropertiesAreNotSerialized(): void
    {
        $propertiesToSerialize = $this->subject->serializeRelatedEntities();

        self::assertNotContains('Flow_Injected_Properties', $propertiesToSerialize);
        self::assertNotContains('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', $propertiesToSerialize);
        self::assertNotContains('Flow_Aop_Proxy_groupedAdviceChains', $propertiesToSerialize);
        self::assertNotContains('Flow_Aop_Proxy_methodIsInAdviceMode', $propertiesToSerialize);
    }

    /**
     * The container itself must be serialized, otherwise the related entities could not be
     * restored on wakeup.
     */
    #[Test]
    public function theRelatedEntitiesContainerIsSerialized(): void
    {
        self::assertContains('Flow_Persistence_RelatedEntitiesContainer', $this->subject->serializeRelatedEntities());
    }

    public static function nonSerializableScopesDataProvider(): array
    {
        return [
            'singleton' => ['scope' => Configuration::SCOPE_SINGLETON],
            'session' => ['scope' => Configuration::SCOPE_SESSION],
        ];
    }

    #[DataProvider('nonSerializableScopesDataProvider')]
    #[Test]
    public function singletonAndSessionScopedObjectsAreNotSerialized(int $scope): void
    {
        $this->subject->objectProperty = new SomeImplementation();
        $this->scopes[SomeImplementation::class] = $scope;

        $propertiesToSerialize = $this->subject->serializeRelatedEntities([], ['objectProperty' => SomeImplementation::class]);

        self::assertNotContains('objectProperty', $propertiesToSerialize);
    }

    #[Test]
    public function prototypeScopedObjectsAreSerialized(): void
    {
        $this->subject->objectProperty = new SomeImplementation();
        $this->scopes[SomeImplementation::class] = Configuration::SCOPE_PROTOTYPE;

        $propertiesToSerialize = $this->subject->serializeRelatedEntities([], ['objectProperty' => SomeImplementation::class]);

        self::assertContains('objectProperty', $propertiesToSerialize);
    }

    #[Test]
    public function dependencyProxiesAreNotSerialized(): void
    {
        $this->subject->objectProperty = new DependencyProxy(SomeImplementation::class, static fn () => new SomeImplementation());

        $propertiesToSerialize = $this->subject->serializeRelatedEntities([], ['objectProperty' => DependencyProxy::class]);

        self::assertNotContains('objectProperty', $propertiesToSerialize);
    }

    #[Test]
    public function theClassNameIsDeterminedFromThePropertyTypeIfNoVarTagExists(): void
    {
        $this->subject->typedObjectProperty = new SomeImplementation();
        $this->scopes[SomeImplementation::class] = Configuration::SCOPE_SINGLETON;

        self::assertNotContains('typedObjectProperty', $this->subject->serializeRelatedEntities());
    }

    #[Test]
    public function theVarTagTakesPrecedenceOverThePropertyTypeAndIsNormalized(): void
    {
        $this->subject->typedObjectProperty = new SomeImplementation();
        $this->scopes['Some\Other\ClassName'] = Configuration::SCOPE_SINGLETON;
        $this->scopes[SomeImplementation::class] = Configuration::SCOPE_PROTOTYPE;

        $propertiesToSerialize = $this->subject->serializeRelatedEntities([], ['typedObjectProperty' => '\\Some\\Other\\ClassName']);

        self::assertNotContains('typedObjectProperty', $propertiesToSerialize);
    }

    #[Test]
    public function theObjectNameIsLookedUpIfTheDeterminedClassNameIsNotRegistered(): void
    {
        $this->subject->typedObjectProperty = new SomeImplementation();
        $this->unregisteredObjectNames = [SomeImplementation::class];
        $this->objectNamesByClassName[SomeImplementation::class] = 'Some.Package:SomeObject';
        $this->scopes['Some.Package:SomeObject'] = Configuration::SCOPE_SINGLETON;

        self::assertNotContains('typedObjectProperty', $this->subject->serializeRelatedEntities());
    }

    #[Test]
    public function persistedEntitiesAreStoredInTheContainerAndNotSerialized(): void
    {
        $entity = new SomeEntity();
        $this->subject->entityProperty = $entity;
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->letPersistenceManagerKnowEntity($entity, 'the-identifier');

        $propertiesToSerialize = $this->subject->serializeRelatedEntities();

        self::assertNotContains('entityProperty', $propertiesToSerialize);
        self::assertSame(
            [
                [
                    'n' => 'entityProperty',
                    'c' => SomeEntity::class,
                    'i' => 'the-identifier',
                    'p' => ''
                ]
            ],
            iterator_to_array($this->subject->Flow_Persistence_RelatedEntitiesContainer)
        );
    }

    #[Test]
    public function doctrineProxiesAreTreatedLikeEntities(): void
    {
        $entity = new SomeEntityDoctrineProxy();
        $this->subject->entityProperty = $entity;
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->mockPersistenceManager->method('getIdentifierByObject')->with($entity)->willReturn('the-identifier');

        $propertiesToSerialize = $this->subject->serializeRelatedEntities();

        self::assertNotContains('entityProperty', $propertiesToSerialize);
        self::assertSame(SomeEntity::class, iterator_to_array($this->subject->Flow_Persistence_RelatedEntitiesContainer)[0]['c']);
    }

    #[Test]
    public function entitiesWhichHaveNotBeenPersistedYetAreSerializedLikeOrdinaryObjects(): void
    {
        $this->subject->entityProperty = new SomeEntity();
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->mockPersistenceManager->method('isNewObject')->willReturn(true);

        $propertiesToSerialize = $this->subject->serializeRelatedEntities();

        self::assertContains('entityProperty', $propertiesToSerialize);
        self::assertSame([], iterator_to_array($this->subject->Flow_Persistence_RelatedEntitiesContainer));
    }

    #[Test]
    public function entitiesInsideArraysAreStoredInTheContainerAndReplacedByNull(): void
    {
        $entity = new SomeEntity();
        $this->subject->arrayProperty = ['first' => $entity, 'second' => 'a string'];
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->letPersistenceManagerKnowEntity($entity, 'the-identifier');

        $propertiesToSerialize = $this->subject->serializeRelatedEntities();

        self::assertContains('arrayProperty', $propertiesToSerialize);
        self::assertSame(['first' => null, 'second' => 'a string'], $this->subject->arrayProperty);

        $entityInformations = iterator_to_array($this->subject->Flow_Persistence_RelatedEntitiesContainer);
        self::assertSame('arrayProperty', $entityInformations[0]['n']);
        self::assertSame('first', $entityInformations[0]['p']);
        self::assertSame('the-identifier', $entityInformations[0]['i']);
    }

    #[Test]
    public function entitiesInsideNestedArraysAreStoredWithTheirFullPath(): void
    {
        $entity = new SomeEntity();
        $this->subject->arrayProperty = ['first' => ['second' => $entity]];
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->letPersistenceManagerKnowEntity($entity, 'the-identifier');

        $this->subject->serializeRelatedEntities();

        self::assertSame(['first' => ['second' => null]], $this->subject->arrayProperty);
        self::assertSame('first.second', iterator_to_array($this->subject->Flow_Persistence_RelatedEntitiesContainer)[0]['p']);
    }

    #[Test]
    public function entitiesInsideDoctrineCollectionsAreStoredInTheContainer(): void
    {
        $entity = new SomeEntity();
        $this->subject->collectionProperty = new ArrayCollection(['first' => $entity]);
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->letPersistenceManagerKnowEntity($entity, 'the-identifier');

        $propertiesToSerialize = $this->subject->serializeRelatedEntities();

        self::assertContains('collectionProperty', $propertiesToSerialize);
        self::assertNull($this->subject->collectionProperty['first']);

        $entityInformations = iterator_to_array($this->subject->Flow_Persistence_RelatedEntitiesContainer);
        self::assertSame('collectionProperty', $entityInformations[0]['n']);
        self::assertSame('first', $entityInformations[0]['p']);
    }

    #[Test]
    public function anExceptionIsThrownIfAnEntityIsFoundButNoContainerExists(): void
    {
        $entity = new SomeEntity();
        $this->subject->arrayProperty = ['first' => $entity];
        $this->letPersistenceManagerKnowEntity($entity, 'the-identifier');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1756936954);

        $this->subject->serializeRelatedEntities();
    }

    #[Test]
    public function setRelatedEntitiesRestoresEntitiesAtTheStoredPaths(): void
    {
        $entity = new SomeEntity();
        $restoredEntity = new SomeEntity('restored');

        $this->subject->arrayProperty = ['first' => $entity];
        $this->subject->entityProperty = $entity;
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->letPersistenceManagerKnowEntity($entity, 'the-identifier');
        $this->subject->serializeRelatedEntities();

        $this->mockPersistenceManager->method('getObjectByIdentifier')->with('the-identifier', SomeEntity::class, true)->willReturn($restoredEntity);

        $this->subject->setRelatedEntities();

        self::assertSame($restoredEntity, $this->subject->arrayProperty['first']);
        self::assertSame($restoredEntity, $this->subject->entityProperty);
    }

    #[Test]
    public function setRelatedEntitiesResetsTheContainer(): void
    {
        $entity = new SomeEntity();
        $this->subject->entityProperty = $entity;
        $this->subject->Flow_Persistence_RelatedEntitiesContainer = new RelatedEntitiesContainer();
        $this->letPersistenceManagerKnowEntity($entity, 'the-identifier');
        $this->subject->serializeRelatedEntities();
        $this->mockPersistenceManager->method('getObjectByIdentifier')->willReturn($entity);

        $this->subject->setRelatedEntities();

        self::assertSame([], iterator_to_array($this->subject->Flow_Persistence_RelatedEntitiesContainer));
    }

    #[Test]
    public function setRelatedEntitiesDoesNothingIfNoContainerExists(): void
    {
        $this->mockPersistenceManager->expects(self::never())->method('getObjectByIdentifier');

        $this->subject->setRelatedEntities();

        self::assertNull($this->subject->Flow_Persistence_RelatedEntitiesContainer);
    }
}
