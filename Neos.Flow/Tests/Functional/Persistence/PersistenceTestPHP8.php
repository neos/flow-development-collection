<?php
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
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Doctrine\QueryResult;
use Neos\Flow\Persistence\Exception;
use Neos\Flow\Persistence\Exception\ObjectValidationFailedException;
use Neos\Flow\Tests\Functional\Persistence\FixturesPHP8\EnumForAProperty;
use Neos\Flow\Tests\Functional\Persistence\FixturesPHP8\TestEntity;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\ObjectAccess;

/**
 * Testcase for persistence
 *
 */
class PersistenceTestPHP8 extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var FixturesPHP8\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @var FixturesPHP8\ExtendedTypesEntityRepository
     */
    protected $extendedTypesEntityRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $earlyEntityManager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->earlyEntityManager = self::$bootstrap->getObjectManager()->get(EntityManagerInterface::class);
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->testEntityRepository = new FixturesPHP8\TestEntityRepository();
        $this->extendedTypesEntityRepository = new FixturesPHP8\ExtendedTypesEntityRepository();
    }

    /**
     * @test
     */
    public function entityManagerIsSingletonInstanceInPersistenceManager()
    {
        $this->earlyEntityManager->persist(new FixturesPHP8\TestEntity());
        self::assertTrue($this->persistenceManager->hasUnpersistedChanges());
    }

    /**
     * @test
     */
    public function entitiesArePersistedAndReconstituted()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        self::assertEquals('Flow', $testEntity->getName());
    }

    /**
     * @test
     */
    public function executingAQueryWillOnlyExecuteItLazily()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $allResults = $this->testEntityRepository->findAll();
        self::assertInstanceOf(QueryResult::class, $allResults);
        self::assertNull(ObjectAccess::getProperty($allResults, 'rows', true), 'Query Result did not load the result collection lazily.');

        $allResultsArray = $allResults->toArray();
        self::assertStringContainsString('Flow', $allResultsArray[0]->getName());
        self::assertIsArray(ObjectAccess::getProperty($allResults, 'rows', true));
    }

    /**
     * @test
     */
    public function serializingAQueryResultWillResetCachedResult()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $allResults = $this->testEntityRepository->findAll();

        $unserializedResults = unserialize(serialize($allResults));
        self::assertNull(ObjectAccess::getProperty($unserializedResults, 'rows', true), 'Query Result did not flush the result collection after serialization.');
    }

    /**
     * @test
     */
    public function resultCanStillBeTraversedAfterSerialization()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();

        $allResults = $this->testEntityRepository->findAll();
        self::assertEquals(1, count($allResults->toArray()), 'Not correct number of entities found before running test.');

        $unserializedResults = unserialize(serialize($allResults));
        self::assertEquals(1, count($unserializedResults->toArray()));
        self::assertEquals('Flow', $unserializedResults[0]->getName());
    }

    /**
     * @test
     */
    public function getFirstShouldNotHaveSideEffects()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity('Flow');
        $this->insertExampleEntity('Neos');

        $allResults = $this->testEntityRepository->findAll();
        self::assertEquals('Flow', $allResults->getFirst()->getName());

        $numberOfTotalResults = count($allResults->toArray());
        self::assertEquals(2, $numberOfTotalResults);
    }

    /**
     * @test
     */
    public function aClonedEntityWillGetANewIdentifier()
    {
        $testEntity = new FixturesPHP8\TestEntity();
        $firstIdentifier = $this->persistenceManager->getIdentifierByObject($testEntity);

        $clonedEntity = clone $testEntity;
        $secondIdentifier = $this->persistenceManager->getIdentifierByObject($clonedEntity);
        self::assertNotEquals($firstIdentifier, $secondIdentifier);
    }

    /**
     * @test
     */
    public function persistedEntitiesLyingInArraysAreNotSerializedButReferencedByTheirIdentifierAndReloadedFromPersistenceOnWakeup()
    {
        $testEntityLyingInsideTheArray = new FixturesPHP8\TestEntity();
        $testEntityLyingInsideTheArray->setName('Flow');

        $arrayProperty = [
            'some' => [
                'nestedArray' => [
                    'key' => $testEntityLyingInsideTheArray
                ]
            ]
        ];

        $testEntityWithArrayProperty = new FixturesPHP8\TestEntity();
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

    /**
     * @test
     */
    public function objectsWithPersistedEntitiesCanBeSerializedMultipleTimes()
    {
        $persistedEntity = new FixturesPHP8\TestEntity();
        $persistedEntity->setName('Flow');
        $this->testEntityRepository->add($persistedEntity);
        $this->persistenceManager->persistAll();

        $objectHoldingTheEntity = new FixturesPHP8\ObjectHoldingAnEntity();
        $objectHoldingTheEntity->testEntity = $persistedEntity;

        for ($i = 0; $i < 2; $i++) {
            $serializedData = serialize($objectHoldingTheEntity);
            $unserializedObjectHoldingTheEntity = unserialize($serializedData);
            $this->assertInstanceOf(FixturesPHP8\TestEntity::class, $unserializedObjectHoldingTheEntity->testEntity);
        }
    }

    /**
     * @test
     */
    public function newEntitiesWhichAreNotAddedToARepositoryYetAreAlreadyKnownToGetObjectByIdentifier()
    {
        $expectedEntity = new FixturesPHP8\TestEntity();
        $uuid = $this->persistenceManager->getIdentifierByObject($expectedEntity);
        $actualEntity = $this->persistenceManager->getObjectByIdentifier($uuid, FixturesPHP8\TestEntity::class);
        self::assertSame($expectedEntity, $actualEntity);
    }

    /**
     * @test
     */
    public function valueObjectsWithTheSameValueAreOnlyPersistedOnce()
    {
        $valueObject1 = new FixturesPHP8\TestValueObject('sameValue');
        $valueObject2 = new FixturesPHP8\TestValueObject('sameValue');

        $testEntity1 = new FixturesPHP8\TestEntity();
        $testEntity1->setRelatedValueObject($valueObject1);
        $testEntity2 = new FixturesPHP8\TestEntity();
        $testEntity2->setRelatedValueObject($valueObject2);

        $this->testEntityRepository->add($testEntity1);
        $this->testEntityRepository->add($testEntity2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $testEntities = $this->testEntityRepository->findAll();

        self::assertSame($testEntities[0]->getRelatedValueObject(), $testEntities[1]->getRelatedValueObject());
    }

    /**
     * @test
     */
    public function alreadyPersistedValueObjectsAreCorrectlyReused()
    {
        $valueObject1 = new FixturesPHP8\TestValueObject('sameValue');
        $testEntity1 = new FixturesPHP8\TestEntity();
        $testEntity1->setRelatedValueObject($valueObject1);

        $this->testEntityRepository->add($testEntity1);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $valueObject2 = new FixturesPHP8\TestValueObject('sameValue');
        $testEntity2 = new FixturesPHP8\TestEntity();
        $testEntity2->setRelatedValueObject($valueObject2);

        $valueObject3 = new FixturesPHP8\TestValueObject('sameValue');
        $testEntity3 = new FixturesPHP8\TestEntity();
        $testEntity3->setRelatedValueObject($valueObject3);

        $this->testEntityRepository->add($testEntity2);
        $this->testEntityRepository->add($testEntity3);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $testEntities = $this->testEntityRepository->findAll();

        self::assertSame($testEntities[0]->getRelatedValueObject(), $testEntities[1]->getRelatedValueObject());
        self::assertSame($testEntities[1]->getRelatedValueObject(), $testEntities[2]->getRelatedValueObject());
    }

    /**
     * @test
     */
    public function embeddedValueObjectsAreActuallyEmbedded()
    {
        /* @var $entityManager EntityManagerInterface */
        $entityManager = $this->objectManager->get(\Doctrine\ORM\EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $classMetaData = $entityManager->getClassMetadata(FixturesPHP8\TestEntity::class);
        self::assertTrue($classMetaData->hasField('embeddedValueObject.value'), 'ClassMetadata is not correctly embedded');
        $schema = $schemaTool->getSchemaFromMetadata([$classMetaData]);
        self::assertTrue($schema->getTable('persistence_php8_testentity')->hasColumn('embeddedvalueobjectvalue'), 'Database schema is missing embedded field');

        $valueObject = new FixturesPHP8\TestEmbeddedValueObject('someValue');
        $testEntity = new FixturesPHP8\TestEntity();
        $testEntity->setEmbeddedValueObject($valueObject);

        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /* @var $testEntity FixturesPHP8\TestEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        self::assertEquals('someValue', $testEntity->getEmbeddedValueObject()->getValue());
    }

    /**
     * @test
     */
    public function validationIsDoneForNewEntities()
    {
        $this->expectException(ObjectValidationFailedException::class);
        $this->removeExampleEntities();
        $this->insertExampleEntity('A');

        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function validationIsDoneForReconstitutedEntities()
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
     *
     * @test
     */
    public function validationIsDoneForReconstitutedEntitiesWhichAreLazyLoadingProxies()
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
        $lazyLoadedEntity = $entityManager->getReference(FixturesPHP8\TestEntity::class, $theObjectIdentifier);
        $lazyLoadedEntity->setName('a');
        $this->testEntityRepository->update($lazyLoadedEntity);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function validationIsOnlyDoneForPropertiesWhichAreInTheDefaultOrPersistencePropertyGroup()
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

    /**
     * @test
     */
    public function eventSubscribersAreProperlyExecuted()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();
        $eventSubscriber = $this->objectManager->get(FixturesPHP8\EventSubscriber::class);
        self::assertTrue($eventSubscriber->preFlushCalled, 'Assert that preFlush event was triggered.');
        self::assertTrue($eventSubscriber->onFlushCalled, 'Assert that onFlush event was triggered.');
        self::assertTrue($eventSubscriber->postFlushCalled, 'Assert that postFlush event was triggered.');
    }

    /**
     * @test
     */
    public function eventListenersAreProperlyExecuted()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();
        $eventSubscriber = $this->objectManager->get(FixturesPHP8\EventListener::class);
        self::assertTrue($eventSubscriber->preFlushCalled, 'Assert that preFlush event was triggered.');
        self::assertTrue($eventSubscriber->onFlushCalled, 'Assert that onFlush event was triggered.');
        self::assertTrue($eventSubscriber->postFlushCalled, 'Assert that postFlush event was triggered.');
    }

    /**
     * @test
     */
    public function persistAllThrowsExceptionIfNonAllowedObjectsAreDirtyAndFlagIsSet()
    {
        $this->expectException(Exception::class);
        $testEntity = new FixturesPHP8\TestEntity();
        $testEntity->setName('Surfer girl');
        $this->testEntityRepository->add($testEntity);
        $this->persistenceManager->persistAll(true);
    }

    /**
     * @test
     */
    public function persistAllThrowsExceptionIfNonAllowedObjectsAreUpdatedAndFlagIsSet()
    {
        $this->expectException(Exception::class);
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();

        /** @var FixturesPHP8\TestEntity $testEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        $testEntity->setName('Another name');
        $this->testEntityRepository->update($testEntity);
        $this->persistenceManager->persistAll(true);
    }

    /**
     * @test
     */
    public function persistAllThrowsNoExceptionIfAllowedObjectsAreDirtyAndFlagIsSet()
    {
        $testEntity = new FixturesPHP8\TestEntity();
        $testEntity->setName('Surfer girl');
        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->allowObject($testEntity);
        $this->persistenceManager->persistAll(true);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function extendedTypesEntityIsIsReconstitutedWithProperties()
    {
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();

        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertNull($persistedExtendedTypesEntity->getCommonObject(), 'Common Object');
        self::assertNull($persistedExtendedTypesEntity->getDateTime(), 'DateTime');
        self::assertNull($persistedExtendedTypesEntity->getDateTimeTz(), 'DateTimeTz');
        self::assertNull($persistedExtendedTypesEntity->getDate(), 'Date');
        self::assertNull($persistedExtendedTypesEntity->getTime(), 'Time');

        // These types always returns an array, never NULL, even if the property is nullable
        self::assertEquals([], $persistedExtendedTypesEntity->getSimpleArray(), 'Simple Array');
        self::assertEquals([], $persistedExtendedTypesEntity->getJsonArray(), 'Json Array');
    }

    /**
     * @test
     */
    public function commonObjectIsPersistedAndIsReconstituted()
    {
        if ($this->objectManager->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.persistence.backendOptions.driver') === 'pdo_pgsql') {
            $this->markTestSkipped('Doctrine ORM on PostgreSQL cannot store serialized data, thus storing objects with Type::OBJECT would fail. See http://www.doctrine-project.org/jira/browse/DDC-3241');
        }

        $commonObject = new FixturesPHP8\CommonObject();
        $commonObject->setFoo('foo');

        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setCommonObject($commonObject);

        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf(FixturesPHP8\CommonObject::class, $persistedExtendedTypesEntity->getCommonObject());
        self::assertEquals('foo', $persistedExtendedTypesEntity->getCommonObject()->getFoo());
    }

    /**
     * @test
     */
    public function jsonArrayIsPersistedAndIsReconstituted()
    {
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setJsonArray(['foo' => 'bar']);

        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals(['foo' => 'bar'], $persistedExtendedTypesEntity->getJsonArray());
    }

    /**
     * @test
     * @see http://doctrine-orm.readthedocs.org/en/latest/cookbook/working-with-datetime.html#default-timezone-gotcha
     */
    public function dateTimeIsPersistedAndIsReconstitutedWithTimeDiffIfSystemTimeZoneDifferentToDateTimeObjectsTimeZone()
    {
        // Make sure running in specific mode independent from testing env settings
        ini_set('date.timezone', 'Arctic/Longyearbyen');

        $dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone('UTC'));
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setDateTime($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        // Restore test env timezone
        ini_restore('date.timezone');

        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTime());
        self::assertNotEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTime()->getTimestamp());
        self::assertEquals('Arctic/Longyearbyen', $persistedExtendedTypesEntity->getDateTime()->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function dateTimeIsPersistedAndIsReconstituted()
    {
        $dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone(ini_get('date.timezone')));
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setDateTime($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTime());
        self::assertEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTime()->getTimestamp());
        self::assertEquals(ini_get('date.timezone'), $persistedExtendedTypesEntity->getDateTime()->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function immutableDateTimeIsPersistedAndIsReconstituted()
    {
        $dateTimeTz = new \DateTimeImmutable('2008-11-16 19:03:30', new \DateTimeZone(ini_get('date.timezone')));
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setDateTimeImmutable($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTimeImmutable', $persistedExtendedTypesEntity->getDateTimeImmutable());
        self::assertEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTimeImmutable()->getTimestamp());
        self::assertEquals(ini_get('date.timezone'), $persistedExtendedTypesEntity->getDateTimeImmutable()->getTimezone()->getName());
    }

    /**
     * This test covers a b/c "feature" that automatically maps var \DateTimeInterface to doctrine `datetime` type without a ORM\Column annotation
     * See #1673
     * @test
     */
    public function dateTimeInterfaceIsPersistedAndIsReconstitutedAsDateTime()
    {
        $dateTimeTz = new \DateTimeImmutable('2008-11-16 19:03:30', new \DateTimeZone(ini_get('date.timezone')));
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setDateTimeInterface($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        // We don't get the same instance out that we put in.
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTimeInterface());
        self::assertEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTimeInterface()->getTimestamp());
        self::assertEquals(ini_get('date.timezone'), $persistedExtendedTypesEntity->getDateTimeInterface()->getTimezone()->getName());
    }

    /**
     * @test
     * @todo We need different tests at least for two types of database.
     * * 1. mysql without timezone support.
     * * 2. a db with timezone support.
     * But since flow does not support multiple db endpoints this is a test just for mysql.
     * In case of mysql, Doctrine handles datetimetz fields simply the same way as datetime does (pure string with date and time but without tz)
     */
    public function dateTimeTzIsPersistedAndIsReconstituted()
    {
        $this->markTestIncomplete('We need different tests at least for two types of database. 1. mysql without timezone support. 2. a db with timezone support.');

        // Make sure running in specific mode independent from testing env settings
        ini_set('date.timezone', 'Arctic/Longyearbyen');

        $dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone('UTC'));
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setDateTimeTz($dateTimeTz);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        // Restore test env timezone
        ini_restore('date.timezone');

        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTimeTz());
        self::assertNotEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTimeTz()->getTimestamp());
        self::assertEquals(ini_get('datetime.timezone'), $persistedExtendedTypesEntity->getDateTimeTz()->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function dateIsPersistedAndIsReconstituted()
    {
        $dateTime = new \DateTime('2008-11-16 19:03:30');
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setDate($dateTime);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals('2008-11-16', $persistedExtendedTypesEntity->getDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function timeIsPersistedAndIsReconstituted()
    {
        $dateTime = new \DateTime('2008-11-16 19:03:30');
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setTime($dateTime);
        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals('19:03:30', $persistedExtendedTypesEntity->getTime()->format('H:i:s'));
    }

    /**
     * @test
     */
    public function simpleArrayIsPersistedAndIsReconstituted()
    {
        $extendedTypesEntity = new FixturesPHP8\ExtendedTypesEntity();
        $extendedTypesEntity->setSimpleArray(['foo' => 'bar']);

        $this->persistenceManager->add($extendedTypesEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /**  @var FixturesPHP8\ExtendedTypesEntity $persistedExtendedTypesEntity */
        $persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

        self::assertInstanceOf(FixturesPHP8\ExtendedTypesEntity::class, $persistedExtendedTypesEntity);
        self::assertEquals(['bar'], $persistedExtendedTypesEntity->getSimpleArray());
    }

    /**
     * @test
     */
    public function hasUnpersistedChangesReturnsTrueAfterObjectUpdate()
    {
        $this->removeExampleEntities();
        $this->insertExampleEntity();
        $this->persistenceManager->persistAll();

        /** @var FixturesPHP8\TestEntity $testEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        $testEntity->setName('Another name');
        $this->testEntityRepository->update($testEntity);
        self::assertTrue($this->persistenceManager->hasUnpersistedChanges());
    }

    public function enumPropertyCanBePersisted()
    {
        $testEntity = new TestEntity();
        $testEntity->setEnumForAProperty(EnumForAProperty::IS_B);
        $this->testEntityRepository->add($testEntity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /** @var FixturesPHP8\TestEntity $testEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        self::assertEquals(EnumForAProperty::IS_B, $testEntity->getEnumForAProperty());
    }

    /**
     * Helper which inserts example data into the database.
     *
     * @param string $name
     */
    protected function insertExampleEntity($name = 'Flow')
    {
        $testEntity = new FixturesPHP8\TestEntity();
        $testEntity->setName($name);
        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * Remove all example entities to enforce a clean state
     */
    protected function removeExampleEntities()
    {
        $this->testEntityRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function doctrineEmbeddablesAreActuallyEmbedded()
    {
        /* @var $entityManager EntityManagerInterface */
        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metaData = $entityManager->getClassMetadata(FixturesPHP8\TestEntity::class);
        self::assertTrue($metaData->hasField('embedded.value'), 'ClassMetadata does not contain embedded value');
        $schema = $schemaTool->getSchemaFromMetadata([$metaData]);
        self::assertTrue($schema->getTable('persistence_php8_testentity')->hasColumn('embedded_value'), 'Database schema does not contain embedded value field');

        $embeddable = new FixturesPHP8\TestEmbeddable('someValue');
        $testEntity = new FixturesPHP8\TestEntity();
        $testEntity->setEmbedded($embeddable);

        $this->testEntityRepository->add($testEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /* @var $testEntity FixturesPHP8\TestEntity */
        $testEntity = $this->testEntityRepository->findAll()->getFirst();
        self::assertEquals('someValue', $testEntity->getEmbedded()->getValue());
    }
}
