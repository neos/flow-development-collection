<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Persistence;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Doctrine\QueryResult;
use Neos\Flow\Persistence\Exception;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\ObjectValidationFailedException;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\EventListener;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\EventSubscriber;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntityRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\ObjectHoldingAnEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEmbeddable;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEmbeddedValueObject;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for persistence
 *
 */
final class PersistenceTest extends FunctionalTestCase
{
    /**
     * @var bool
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Fixtures\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @var Fixtures\ExtendedTypesEntityRepository
     */
    protected $extendedTypesEntityRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $earlyEntityManager;

    /**
     * @return void
     * @throws \Neos\Flow\Exception
     * @throws \Neos\Flow\Exception
     */
    protected function setUp(): void
    {
        $this->earlyEntityManager = self::$bootstrap->getObjectManager()->get(EntityManagerInterface::class);
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            static::markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->testEntityRepository = new TestEntityRepository();
        $this->extendedTypesEntityRepository = new ExtendedTypesEntityRepository();
    }

    #[Test]
    public function entityManagerIsSingletonInstanceInPersistenceManager(): void
    {
        $this->earlyEntityManager->persist(new TestEntity());
        self::assertTrue($this->persistenceManager->hasUnpersistedChanges());
    }

    #[Test]
    public function entitiesArePersistedAndReconstituted(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        self::assertEquals('Flow', $testEntity->getName());
    }

    #[Test]
    public function executingAQueryWillOnlyExecuteItLazily(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $allResults = $this->testEntityRepository->findAll();
        self::assertInstanceOf(QueryResult::class, $allResults);
        self::assertNull(ObjectAccess::getProperty($allResults, 'rows', true), 'Query Result did not load the result collection lazily.');

        $allResultsArray = $allResults->toArray();
        self::assertStringContainsString('Flow', (string) $allResultsArray[0]->getName());
        self::assertIsArray(ObjectAccess::getProperty($allResults, 'rows', true));
    }

    #[Test]
    public function serializingAQueryResultWillResetCachedResult(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $allResults = $this->testEntityRepository->findAll();

        $unserializedResults = unserialize(serialize($allResults));
        self::assertNull(ObjectAccess::getProperty($unserializedResults, 'rows', true), 'Query Result did not flush the result collection after serialization.');
    }

    #[Test]
    public function resultCanStillBeTraversedAfterSerialization(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $allResults = $this->testEntityRepository->findAll();
        self::assertCount(1, $allResults->toArray(), 'Not correct number of entities found before running test.');

        $unserializedResults = unserialize(serialize($allResults));
        self::assertCount(1, $unserializedResults->toArray());
        self::assertEquals('Flow', $unserializedResults[0]->getName());
    }

    #[Test]
    public function getFirstShouldNotHaveSideEffects(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->insertExampleEntity('Neos');

        $allResults = $this->testEntityRepository->findAll();
        self::assertEquals('Flow', $allResults->getFirst()->getName());

        $numberOfTotalResults = count($allResults->toArray());
        self::assertSame(2, $numberOfTotalResults);
    }

    #[Test]
    public function aClonedEntityWillGetANewIdentifier(): void
    {
        $testEntity = new TestEntity();
        $firstIdentifier = $this->persistenceManager->getIdentifierByObject($testEntity);

        $clonedEntity = clone $testEntity;
        $secondIdentifier = $this->persistenceManager->getIdentifierByObject($clonedEntity);
        self::assertNotEquals($firstIdentifier, $secondIdentifier);
    }

    #[Test]
    public function persistedEntitiesLyingInArraysAreNotSerializedButReferencedByTheirIdentifierAndReloadedFromPersistenceOnWakeup(): void
    {
        $testEntityLyingInsideTheArray = new TestEntity();
        $testEntityLyingInsideTheArray->setName('Flow');

        $arrayProperty = [
            'some' => [
                'nestedArray' => [
                    'key' => $testEntityLyingInsideTheArray
                ]
            ]
        ];

        $testEntityWithArrayProperty = new TestEntity();
        $testEntityWithArrayProperty->setName('dummy');
        $testEntityWithArrayProperty->setArrayProperty($arrayProperty);

        $this->testEntityRepository->add($testEntityLyingInsideTheArray);
        $this->testEntityRepository->add($testEntityWithArrayProperty);

        $this->persistenceManager->persistAll();

        $serializedData = serialize($testEntityWithArrayProperty);

        $testEntityLyingInsideTheArray->setName('Neos');
        $this->persistenceManager->persistAll();

        $testEntityWithArrayPropertyUnserialized = unserialize($serializedData);
        $arrayPropertyAfterUnserialize = $testEntityWithArrayPropertyUnserialized->getArrayProperty();

        self::assertNotSame($testEntityWithArrayProperty, $testEntityWithArrayPropertyUnserialized);
        self::assertEquals('Neos', $arrayPropertyAfterUnserialize['some']['nestedArray']['key']->getName(), 'The entity inside the array property has not been updated to the current persistend state after wakeup.');
    }

    #[Test]
    public function objectsWithPersistedEntitiesCanBeSerializedMultipleTimes(): void
    {
        $persistedEntity = new TestEntity();
        $persistedEntity->setName('Flow');
        $this->testEntityRepository->add($persistedEntity);
        $this->persistenceManager->persistAll();

        $objectHoldingTheEntity = new ObjectHoldingAnEntity();
        $objectHoldingTheEntity->testEntity = $persistedEntity;

        for ($i = 0; $i < 2; $i++) {
            $serializedData = serialize($objectHoldingTheEntity);
            $unserializedObjectHoldingTheEntity = unserialize($serializedData);
            static::assertInstanceOf(TestEntity::class, $unserializedObjectHoldingTheEntity->testEntity);
        }
    }

    #[Test]
    public function newEntitiesWhichAreNotAddedToARepositoryYetAreAlreadyKnownToGetObjectByIdentifier(): void
    {
        $expectedEntity = new TestEntity();
        $uuid = $this->persistenceManager->getIdentifierByObject($expectedEntity);
        $actualEntity = $this->persistenceManager->getObjectByIdentifier($uuid, TestEntity::class);
        self::assertSame($expectedEntity, $actualEntity);
    }

    #[Test]
    public function valueObjectsWithTheSameValueAreOnlyPersistedOnce(): void
    {
        $valueObject1 = new TestValueObject('sameValue');
        $valueObject2 = new TestValueObject('sameValue');

        $testEntity1 = new TestEntity();
        $testEntity1->setRelatedValueObject($valueObject1);
        $testEntity2 = new TestEntity();
        $testEntity2->setRelatedValueObject($valueObject2);

        $this->testEntityRepository->add($testEntity1);
        $this->testEntityRepository->add($testEntity2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $testEntities = $this->testEntityRepository->findAll();

        self::assertSame($testEntities[0]->getRelatedValueObject(), $testEntities[1]->getRelatedValueObject());
    }

    #[Test]
    public function alreadyPersistedValueObjectsAreCorrectlyReused(): void
    {
        $valueObject1 = new TestValueObject('sameValue');
        $testEntity1 = new TestEntity();
        $testEntity1->setRelatedValueObject($valueObject1);

        $this->testEntityRepository->add($testEntity1);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $valueObject2 = new TestValueObject('sameValue');
        $testEntity2 = new TestEntity();
        $testEntity2->setRelatedValueObject($valueObject2);

        $valueObject3 = new TestValueObject('sameValue');
        $testEntity3 = new TestEntity();
        $testEntity3->setRelatedValueObject($valueObject3);

        $this->testEntityRepository->add($testEntity2);
        $this->testEntityRepository->add($testEntity3);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $testEntities = $this->testEntityRepository->findAll();

        self::assertSame($testEntities[0]->getRelatedValueObject(), $testEntities[1]->getRelatedValueObject());
        self::assertSame($testEntities[1]->getRelatedValueObject(), $testEntities[2]->getRelatedValueObject());
    }

    #[Test]
    public function embeddedValueObjectsAreActuallyEmbedded(): void
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $classMetaData = $entityManager->getClassMetadata(TestEntity::class);
        self::assertTrue($classMetaData->hasField('embeddedValueObject.value'), 'ClassMetadata is not correctly embedded');
        $schema = $schemaTool->getSchemaFromMetadata([$classMetaData]);
        self::assertTrue($schema->getTable('persistence_testentity')->hasColumn('embeddedvalueobjectvalue'), 'Database schema is missing embedded field');

        $valueObject = new TestEmbeddedValueObject('someValue');
        $testEntity = new TestEntity();
        $testEntity->setEmbeddedValueObject($valueObject);

        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /* @var Fixtures\TestEntity $testEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        self::assertEquals('someValue', $testEntity->getEmbeddedValueObject()->getValue());
    }

    #[Test]
    public function validationIsDoneForNewEntities(): void
    {
        $this->expectException(ObjectValidationFailedException::class);
        $this->removeExampleEntities();
        $this->insertExampleEntity('A');

        $this->persistenceManager->persistAll();
    }

    #[Test]
    public function validationIsDoneForReconstitutedEntities(): void
    {
        $this->expectException(ObjectValidationFailedException::class);
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();

        $firstResult = $this->testEntityRepository->findAll()->getFirst();
        $firstResult->setName('A');
        $this->testEntityRepository->update($firstResult);
        $this->persistenceManager->persistAll();
    }

    /**
     * Testcase for issue #32830 - Validation on persist breaks with Doctrine Lazy Loading Proxies
     */
    #[Test]
    public function validationIsDoneForReconstitutedEntitiesWhichAreLazyLoadingProxies(): void
    {
        $this->expectException(ObjectValidationFailedException::class);
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();
        $theObject = $this->testEntityRepository->findOneByName('Flow');
        $theObjectIdentifier = $this->persistenceManager->getIdentifierByObject($theObject);

        // Here, we completely reset the persistence manager again and work
        // only with the Object Identifier
        $this->persistenceManager->clearState();

        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $lazyLoadedEntity = $entityManager->getReference(TestEntity::class, $theObjectIdentifier);
        $lazyLoadedEntity->setName('a');
        $this->testEntityRepository->update($lazyLoadedEntity);
        $this->persistenceManager->persistAll();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function validationIsOnlyDoneForPropertiesWhichAreInTheDefaultOrPersistencePropertyGroup(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();
        $testEntity = $this->testEntityRepository->findOneByName('Flow');

        // We now make the TestEntities Description *invalid*, and still
        // expect that the saving works without exception.
        $testEntity->setDescription('');
        $this->testEntityRepository->update($testEntity);
        $this->persistenceManager->persistAll();
    }

    #[Test]
    public function eventSubscribersAreProperlyExecuted(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();
        $eventSubscriber = $this->objectManager->get(EventSubscriber::class);
        self::assertTrue($eventSubscriber->preFlushCalled, 'Assert that preFlush event was triggered.');
        self::assertTrue($eventSubscriber->onFlushCalled, 'Assert that onFlush event was triggered.');
        self::assertTrue($eventSubscriber->postFlushCalled, 'Assert that postFlush event was triggered.');
    }

    #[Test]
    public function eventListenersAreProperlyExecuted(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();
        $eventSubscriber = $this->objectManager->get(EventListener::class);
        self::assertTrue($eventSubscriber->preFlushCalled, 'Assert that preFlush event was triggered.');
        self::assertTrue($eventSubscriber->onFlushCalled, 'Assert that onFlush event was triggered.');
        self::assertTrue($eventSubscriber->postFlushCalled, 'Assert that postFlush event was triggered.');
    }

    #[Test]
    public function persistAllThrowsExceptionIfNonAllowedObjectsAreDirtyAndFlagIsSet(): void
    {
        $this->expectException(Exception::class);
        $testEntity = new TestEntity();
        $testEntity->setName('Surfer girl');
        $this->testEntityRepository->add($testEntity);
        $this->persistenceManager->persistAll(true);
    }

    #[Test]
    public function persistAllThrowsExceptionIfNonAllowedObjectsAreUpdatedAndFlagIsSet(): void
    {
        $this->expectException(Exception::class);
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();

        /** @var Fixtures\TestEntity $testEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        $testEntity->setName('Another name');
        $this->testEntityRepository->update($testEntity);
        $this->persistenceManager->persistAll(true);
    }

    #[Test]
    public function persistAllThrowsNoExceptionIfAllowedObjectsAreDirtyAndFlagIsSet(): void
    {
        $testEntity = new TestEntity();
        $testEntity->setName('Surfer girl');
        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->allowObject($testEntity);
        $this->persistenceManager->persistAll(true);
        self::assertTrue(true);
    }

    #[Test]
    public function extendedTypesEntityIsIsReconstitutedWithProperties(): void
    {
        $extendedTypesEntity = new ExtendedTypesEntity();

        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertNull($persistedExtendedTypesEntity->getDateTime(), 'DateTime');
        self::assertNull($persistedExtendedTypesEntity->getDateTimeTz(), 'DateTimeTz');
        self::assertNull($persistedExtendedTypesEntity->getDate(), 'Date');
        self::assertNull($persistedExtendedTypesEntity->getTime(), 'Time');
        self::assertNull($persistedExtendedTypesEntity->getJsonArray(), 'Json Array');

        // These types always returns an array, never NULL, even if the property is nullable
        self::assertEquals([], $persistedExtendedTypesEntity->getSimpleArray(), 'Simple Array');
    }

    #[Test]
    public function jsonArrayIsPersistedAndIsReconstituted(): void
    {
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setJsonArray(['foo' => 'bar']);

        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals(['foo' => 'bar'], $persistedExtendedTypesEntity->getJsonArray());
    }

    /**
     * @see http://doctrine-orm.readthedocs.org/en/latest/cookbook/working-with-datetime.html#default-timezone-gotcha
     */
    #[Test]
    public function dateTimeIsPersistedAndIsReconstitutedWithTimeDiffIfSystemTimeZoneDifferentToDateTimeObjectsTimeZone(): void
    {
        // Make sure running in specific mode independent from testing env settings
        ini_set('date.timezone', 'Arctic/Longyearbyen');

        $dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone('UTC'));
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setDateTime($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        // Restore test env timezone
        ini_restore('date.timezone');

        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTime());
        self::assertNotEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTime()->getTimestamp());
        self::assertSame('Arctic/Longyearbyen', $persistedExtendedTypesEntity->getDateTime()->getTimezone()->getName());
    }

    #[Test]
    public function dateTimeIsPersistedAndIsReconstituted(): void
    {
        $dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone(ini_get('date.timezone')));
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setDateTime($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTime());
        self::assertEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTime()->getTimestamp());
        self::assertSame(ini_get('date.timezone'), $persistedExtendedTypesEntity->getDateTime()->getTimezone()->getName());
    }

    #[Test]
    public function immutableDateTimeIsPersistedAndIsReconstituted(): void
    {
        $dateTimeTz = new \DateTimeImmutable('2008-11-16 19:03:30', new \DateTimeZone(ini_get('date.timezone')));
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setDateTimeImmutable($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTimeImmutable', $persistedExtendedTypesEntity->getDateTimeImmutable());
        self::assertEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTimeImmutable()->getTimestamp());
        self::assertSame(ini_get('date.timezone'), $persistedExtendedTypesEntity->getDateTimeImmutable()->getTimezone()->getName());
    }

    /**
     * This test covers a b/c "feature" that automatically maps var \DateTimeInterface to doctrine `datetime` type without a ORM\Column annotation
     * See #1673
     */
    #[Test]
    public function dateTimeInterfaceIsPersistedAndIsReconstitutedAsDateTime(): void
    {
        $dateTimeTz = new \DateTimeImmutable('2008-11-16 19:03:30', new \DateTimeZone(ini_get('date.timezone')));
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setDateTimeInterface($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        // We don't get the same instance out that we put in.
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTimeInterface());
        self::assertEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTimeInterface()->getTimestamp());
        self::assertSame(ini_get('date.timezone'), $persistedExtendedTypesEntity->getDateTimeInterface()->getTimezone()->getName());
    }

    /**
     * @todo We need different tests at least for two types of database.
     * * 1. mysql without timezone support.
     * * 2. a db with timezone support.
     * But since flow does not support multiple db endpoints this is a test just for mysql.
     * In case of mysql, Doctrine handles datetimetz fields simply the same way as datetime does (pure string with date and time but without tz)
     */
    #[Test]
    public function dateTimeTzIsPersistedAndIsReconstituted(): void
    {
        static::markTestIncomplete('We need different tests at least for two types of database. 1. mysql without timezone support. 2. a db with timezone support.');

        // Make sure running in specific mode independent from testing env settings
        ini_set('date.timezone', 'Arctic/Longyearbyen');

        $dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone('UTC'));
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setDateTimeTz($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        // Restore test env timezone
        ini_restore('date.timezone');

        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTimeTz());
        self::assertNotEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTimeTz()->getTimestamp());
        self::assertEquals(ini_get('datetime.timezone'), $persistedExtendedTypesEntity->getDateTimeTz()->getTimezone()->getName());
    }

    #[Test]
    public function dateIsPersistedAndIsReconstituted(): void
    {
        $dateTime = new \DateTime('2008-11-16 19:03:30');
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setDate($dateTime);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals('2008-11-16', $persistedExtendedTypesEntity->getDate()->format('Y-m-d'));
    }

    #[Test]
    public function timeIsPersistedAndIsReconstituted(): void
    {
        $dateTime = new \DateTime('2008-11-16 19:03:30');
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setTime($dateTime);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals('19:03:30', $persistedExtendedTypesEntity->getTime()->format('H:i:s'));
    }

    #[Test]
    public function simpleArrayIsPersistedAndIsReconstituted(): void
    {
        $extendedTypesEntity = new ExtendedTypesEntity();
        $extendedTypesEntity->setSimpleArray(['foo' => 'bar']);

        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var Fixtures\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        self::assertInstanceOf(ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals(['bar'], $persistedExtendedTypesEntity->getSimpleArray());
    }

    #[Test]
    public function hasUnpersistedChangesReturnsTrueAfterObjectUpdate(): void
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();

        /** @var Fixtures\TestEntity $testEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        $testEntity->setName('Another name');
        $this->testEntityRepository->update($testEntity);
        self::assertTrue($this->persistenceManager->hasUnpersistedChanges());
    }

    /**
     * Helper which inserts example data into the database.
     *
     * @param string $name
     * @throws IllegalObjectTypeException
     * @throws IllegalObjectTypeException
     */
    protected function insertExampleEntity($name = 'Flow'): void
    {
        $testEntity = new TestEntity();
        $testEntity->setName($name);
        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * Remove all example entities to enforce a clean state
     */
    protected function removeExampleEntities(): void
    {
        $this->testEntityRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function doctrineEmbeddablesAreActuallyEmbedded(): void
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metaData = $entityManager->getClassMetadata(TestEntity::class);
        self::assertTrue($metaData->hasField('embedded.value'), 'ClassMetadata does not contain embedded value');
        $schema = $schemaTool->getSchemaFromMetadata([$metaData]);
        self::assertTrue($schema->getTable('persistence_testentity')->hasColumn('embedded_value'), 'Database schema does not contain embedded value field');

        $embeddable = new TestEmbeddable('someValue');
        $testEntity = new TestEntity();
        $testEntity->setEmbedded($embeddable);

        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /* @var Fixtures\TestEntity $testEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        self::assertEquals('someValue', $testEntity->getEmbedded()->getValue());
    }
}
