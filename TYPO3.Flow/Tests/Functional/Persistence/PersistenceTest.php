<?php
namespace TYPO3\Flow\Tests\Functional\Persistence;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\CommonObject;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntityRepository;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;

/**
 * Testcase for persistence
 *
 */
class PersistenceTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var TestEntityRepository
	 */
	protected $testEntityRepository;

	/**
	 * @var ExtendedTypesEntityRepository
	 */
	protected $extendedTypesEntityRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->testEntityRepository = new TestEntityRepository();
		$this->extendedTypesEntityRepository = new ExtendedTypesEntityRepository();
	}

	/**
	 * @test
	 */
	public function entitiesArePersistedAndReconstituted() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$testEntity = $this->testEntityRepository->findAll()->getFirst();
		$this->assertEquals('Flow', $testEntity->getName());
	}

	/**
	 * @test
	 */
	public function executingAQueryWillOnlyExecuteItLazily() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();
		$this->assertInstanceOf('TYPO3\Flow\Persistence\Doctrine\QueryResult', $allResults);
		$this->assertAttributeInternalType('null', 'rows', $allResults, 'Query Result did not load the result collection lazily.');

		$allResultsArray = $allResults->toArray();
		$this->assertEquals('Flow', $allResultsArray[0]->getName());
		$this->assertAttributeInternalType('array', 'rows', $allResults);
	}

	/**
	 * @test
	 */
	public function serializingAQueryResultWillResetCachedResult() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();

		$unserializedResults = unserialize(serialize($allResults));
		$this->assertAttributeInternalType('null', 'rows', $unserializedResults, 'Query Result did not flush the result collection after serialization.');
	}

	/**
	 * @test
	 */
	public function resultCanStillBeTraversedAfterSerialization() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();

		$allResults = $this->testEntityRepository->findAll();
		$this->assertEquals(1, count($allResults->toArray()), 'Not correct number of entities found before running test.');

		$unserializedResults = unserialize(serialize($allResults));
		$this->assertEquals(1, count($unserializedResults->toArray()));
		$this->assertEquals('Flow', $unserializedResults[0]->getName());
	}

	/**
	 * @test
	 */
	public function getFirstShouldNotHaveSideEffects() {
		$this->removeExampleEntities();
		$this->insertExampleEntity('Flow');
		$this->insertExampleEntity('TYPO3');

		$allResults = $this->testEntityRepository->findAll();
		$this->assertEquals('Flow', $allResults->getFirst()->getName());

		$numberOfTotalResults = count($allResults->toArray());
		$this->assertEquals(2, $numberOfTotalResults);
	}

	/**
	 * @test
	 */
	public function aClonedEntityWillGetANewIdentifier() {
		$testEntity = new TestEntity();
		$firstIdentifier = $this->persistenceManager->getIdentifierByObject($testEntity);

		$clonedEntity = clone $testEntity;
		$secondIdentifier = $this->persistenceManager->getIdentifierByObject($clonedEntity);
		$this->assertNotEquals($firstIdentifier, $secondIdentifier);
	}

	/**
	 * @test
	 */
	public function persistedEntitiesLyingInArraysAreNotSerializedButReferencedByTheirIdentifierAndReloadedFromPersistenceOnWakeup() {
		$testEntityLyingInsideTheArray = new TestEntity();
		$testEntityLyingInsideTheArray->setName('Flow');

		$arrayProperty = array(
			'some' => array(
				'nestedArray' => array(
					'key' => $testEntityLyingInsideTheArray
				)
			)
		);

		$testEntityWithArrayProperty = new TestEntity();
		$testEntityWithArrayProperty->setName('dummy');
		$testEntityWithArrayProperty->setArrayProperty($arrayProperty);

		$this->testEntityRepository->add($testEntityLyingInsideTheArray);
		$this->testEntityRepository->add($testEntityWithArrayProperty);

		$this->persistenceManager->persistAll();

		$serializedData = serialize($testEntityWithArrayProperty);

		$testEntityLyingInsideTheArray->setName('TYPO3');
		$this->persistenceManager->persistAll();

		$testEntityWithArrayPropertyUnserialized = unserialize($serializedData);
		$arrayPropertyAfterUnserialize = $testEntityWithArrayPropertyUnserialized->getArrayProperty();

		$this->assertNotSame($testEntityWithArrayProperty, $testEntityWithArrayPropertyUnserialized);
		$this->assertEquals('TYPO3', $arrayPropertyAfterUnserialize['some']['nestedArray']['key']->getName(), 'The entity inside the array property has not been updated to the current persistend state after wakeup.');
	}

	/**
	 * @test
	 */
	public function newEntitiesWhichAreNotAddedToARepositoryYetAreAlreadyKnownToGetObjectByIdentifier() {
		$expectedEntity = new TestEntity();
		$uuid = $this->persistenceManager->getIdentifierByObject($expectedEntity);
		$actualEntity = $this->persistenceManager->getObjectByIdentifier($uuid, 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');
		$this->assertSame($expectedEntity, $actualEntity);
	}

	/**
	 * @test
	 */
	public function valueObjectsWithTheSameValueAreOnlyPersistedOnce() {
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

		$this->assertSame($testEntities[0]->getRelatedValueObject(), $testEntities[1]->getRelatedValueObject());
	}

	/**
	 * @test
	 */
	public function alreadyPersistedValueObjectsAreCorrectlyReused() {
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

		$this->assertSame($testEntities[0]->getRelatedValueObject(), $testEntities[1]->getRelatedValueObject());
		$this->assertSame($testEntities[1]->getRelatedValueObject(), $testEntities[2]->getRelatedValueObject());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Persistence\Exception\ObjectValidationFailedException
	 */
	public function validationIsDoneForNewEntities() {
		$this->removeExampleEntities();
		$this->insertExampleEntity('A');

		$this->persistenceManager->persistAll();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Persistence\Exception\ObjectValidationFailedException
	 */
	public function validationIsDoneForReconstitutedEntities() {
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
	 * @expectedException \TYPO3\Flow\Persistence\Exception\ObjectValidationFailedException
	 */
	public function validationIsDoneForReconstitutedEntitiesWhichAreLazyLoadingProxies() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();
		$this->persistenceManager->persistAll();
		$theObject = $this->testEntityRepository->findOneByName('Flow');
		$theObjectIdentifier = $this->persistenceManager->getIdentifierByObject($theObject);

		// Here, we completely reset the persistence manager again and work
		// only with the Object Identifier
		$this->persistenceManager->clearState();

		$entityManager = $this->objectManager->get('Doctrine\Common\Persistence\ObjectManager');
		$lazyLoadedEntity = $entityManager->getReference('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity', $theObjectIdentifier);
		$lazyLoadedEntity->setName('a');
		$this->testEntityRepository->update($lazyLoadedEntity);
		$this->persistenceManager->persistAll();
	}

	/**
	 * @test
	 */
	public function validationIsOnlyDoneForPropertiesWhichAreInTheDefaultOrPersistencePropertyGroup() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();
		$this->persistenceManager->persistAll();
		$testEntity = $this->testEntityRepository->findOneByName('Flow');

		// We now make the TestEntities Description *invalid*, and still
		// expect that the saving works without exception.
		$testEntity->setDescription('');
		$this->testEntityRepository->update($testEntity);
		$this->persistenceManager->persistAll();

		// dummy assertion to suppress PHPUnit warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function eventSubscribersAreProperlyExecuted() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();
		$this->persistenceManager->persistAll();
		$eventSubscriber = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\EventSubscriber');
		$this->assertTrue($eventSubscriber->preFlushCalled, 'Assert that preFlush event was triggered.');
		$this->assertTrue($eventSubscriber->onFlushCalled, 'Assert that onFlush event was triggered.');
		$this->assertTrue($eventSubscriber->postFlushCalled, 'Assert that postFlush event was triggered.');
	}

	/**
	 * @test
	 */
	public function eventListenersAreProperlyExecuted() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();
		$this->persistenceManager->persistAll();
		$eventSubscriber = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\EventListener');
		$this->assertTrue($eventSubscriber->preFlushCalled, 'Assert that preFlush event was triggered.');
		$this->assertTrue($eventSubscriber->onFlushCalled, 'Assert that onFlush event was triggered.');
		$this->assertTrue($eventSubscriber->postFlushCalled, 'Assert that postFlush event was triggered.');
	}

	/**
	 * @expectedException \TYPO3\Flow\Persistence\Exception
	 * @test
	 */
	public function persistAllThrowsExceptionIfNonWhitelistedObjectsAreDirtyAndFlagIsSet() {
		$testEntity = new TestEntity();
		$testEntity->setName('Surfer girl');
		$this->testEntityRepository->add($testEntity);
		$this->persistenceManager->persistAll(TRUE);
	}

	/**
	 * @expectedException \TYPO3\Flow\Persistence\Exception
	 * @test
	 */
	public function persistAllThrowsExceptionIfNonWhitelistedObjectsAreUpdatedAndFlagIsSet() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();
		$this->persistenceManager->persistAll();

		/** @var TestEntity $testEntity */
		$testEntity = $this->testEntityRepository->findAll()->getFirst();
		$testEntity->setName('Another name');
		$this->testEntityRepository->update($testEntity);
		$this->persistenceManager->persistAll(TRUE);
	}

	/**
	 * @test
	 */
	public function persistAllThrowsNoExceptionIfWhitelistedObjectsAreDirtyAndFlagIsSet() {
		$testEntity = new TestEntity();
		$testEntity->setName('Surfer girl');
		$this->testEntityRepository->add($testEntity);

		$this->persistenceManager->whitelistObject($testEntity);
		$this->persistenceManager->persistAll(TRUE);
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function extendedTypesEntityIsIsReconstitutedWithProperties() {
		$extendedTypesEntity = new ExtendedTypesEntity();

		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertNull($persistedExtendedTypesEntity->getCommonObject(), 'Common Object');
		$this->assertNull($persistedExtendedTypesEntity->getDateTime(), 'DateTime');
		$this->assertNull($persistedExtendedTypesEntity->getDateTimeTz(), 'DateTimeTz');
		$this->assertNull($persistedExtendedTypesEntity->getDate(), 'Date');
		$this->assertNull($persistedExtendedTypesEntity->getTime(), 'Time');

		// These types always returns an array, never NULL, even if the property is nullable
		$this->assertEquals(array(), $persistedExtendedTypesEntity->getSimpleArray(), 'Simple Array');
		$this->assertEquals(array(), $persistedExtendedTypesEntity->getJsonArray(), 'Json Array');
	}

	/**
	 * @test
	 */
	public function commonObjectIsPersistedAndIsReconstituted() {
		$commonObject = new CommonObject();
		$commonObject->setFoo('foo');

		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setCommonObject($commonObject);

		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\CommonObject', $persistedExtendedTypesEntity->getCommonObject());
		$this->assertEquals('foo', $persistedExtendedTypesEntity->getCommonObject()->getFoo());
	}

	/**
	 * @test
	 */
	public function jsonArrayIsPersistedAndIsReconstituted() {
		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setJsonArray(array('foo' => 'bar'));

		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertEquals(array('foo' => 'bar'), $persistedExtendedTypesEntity->getJsonArray());
	}

	/**
	 * @test
	 * @see http://doctrine-orm.readthedocs.org/en/latest/cookbook/working-with-datetime.html#default-timezone-gotcha
	 */
	public function dateTimeIsPersistedAndIsReconstitutedWithTimeDiffIfSystemTimeZoneDifferentToDateTimeObjectsTimeZone() {
		// Make sure running in specific mode independent from testing env settings
		ini_set('date.timezone', 'Arctic/Longyearbyen');

		$dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone('UTC'));
		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setDateTime($dateTimeTz);
		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

		// Restore test env timezone
		ini_restore('date.timezone');

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTime());
		$this->assertNotEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTime()->getTimestamp());
		$this->assertEquals('Arctic/Longyearbyen', $persistedExtendedTypesEntity->getDateTime()->getTimezone()->getName());
	}

	/**
	 * @test
	 */
	public function dateTimeIsPersistedAndIsReconstituted() {
		$dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone(ini_get('date.timezone')));
		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setDateTime($dateTimeTz);
		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTime());
		$this->assertEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTime()->getTimestamp());
		$this->assertEquals(ini_get('date.timezone'), $persistedExtendedTypesEntity->getDateTime()->getTimezone()->getName());
	}

	/**
	 * @test
	 * @todo We need different tests at least for two types of database.
	 * * 1. mysql without timezone support.
	 * * 2. a db with timezone support.
	 * But since flow does not support multiple db endpoints this is a test just for mysql.
	 * In case of mysql, Doctrine handles datetimetz fields simply the same way as datetime does (pure string with date and time but without tz)
	 */
	public function dateTimeTzIsPersistedAndIsReconstituted() {
		$this->markTestIncomplete('We need different tests at least for two types of database. 1. mysql without timezone support. 2. a db with timezone support.');

		// Make sure running in specific mode independent from testing env settings
		ini_set('date.timezone', 'Arctic/Longyearbyen');

		$dateTimeTz = new \DateTime('2008-11-16 19:03:30', new \DateTimeZone('UTC'));
		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setDateTimeTz($dateTimeTz);
		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

		// Restore test env timezone
		ini_restore('date.timezone');

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertInstanceOf('DateTime', $persistedExtendedTypesEntity->getDateTimeTz());
		$this->assertNotEquals($dateTimeTz->getTimestamp(), $persistedExtendedTypesEntity->getDateTimeTz()->getTimestamp());
		$this->assertEquals(ini_get('datetime.timezone'), $persistedExtendedTypesEntity->getDateTimeTz()->getTimezone()->getName());
	}

	/**
	 * @test
	 */
	public function dateIsPersistedAndIsReconstituted() {
		$dateTime = new \DateTime('2008-11-16 19:03:30');
		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setDate($dateTime);
		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertEquals('2008-11-16', $persistedExtendedTypesEntity->getDate()->format('Y-m-d'));
	}

	/**
	 * @test
	 */
	public function timeIsPersistedAndIsReconstituted() {
		$dateTime = new \DateTime('2008-11-16 19:03:30');
		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setTime($dateTime);
		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertEquals('19:03:30', $persistedExtendedTypesEntity->getTime()->format('H:i:s'));
	}

	/**
	 * @test
	 */
	public function simpleArrayIsPersistedAndIsReconstituted() {
		$extendedTypesEntity = new ExtendedTypesEntity();
		$extendedTypesEntity->setSimpleArray(array('foo' => 'bar'));

		$this->persistenceManager->add($extendedTypesEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		/**  @var ExtendedTypesEntity $persistedExtendedTypesEntity */
		$persistedExtendedTypesEntity = $this->extendedTypesEntityRepository->findAll()->getFirst();

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\ExtendedTypesEntity', $persistedExtendedTypesEntity);
		$this->assertEquals(array('bar'), $persistedExtendedTypesEntity->getSimpleArray());
	}

	/**
	 * @test
	 */
	public function hasUnpersistedChangesReturnsTrueAfterObjectUpdate() {
		$this->removeExampleEntities();
		$this->insertExampleEntity();
		$this->persistenceManager->persistAll();

		/** @var TestEntity $testEntity */
		$testEntity = $this->testEntityRepository->findAll()->getFirst();
		$testEntity->setName('Another name');
		$this->testEntityRepository->update($testEntity);
		$this->assertTrue($this->persistenceManager->hasUnpersistedChanges());
	}

	/**
	 * Helper which inserts example data into the database.
	 *
	 * @param string $name
	 */
	protected function insertExampleEntity($name = 'Flow') {
		$testEntity = new TestEntity();
		$testEntity->setName($name);
		$this->testEntityRepository->add($testEntity);

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}

	/**
	 * Remove all example entities to enforce a clean state
	 */
	protected function removeExampleEntities() {
		$this->testEntityRepository->removeAll();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
	}
}
