<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;

/**
 * Testcase for persisting cloned related entities
 */
class PersistClonedRelatedEntitiesTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository;
	 */
	protected $testEntityRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->testEntityRepository = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository');
	}

	/**
	 * @test
	 */
	public function relatedEntitiesCanBePersistedWhenFetchedAsDoctrineProxy() {
		$entity = new TestEntity();
		$entity->setName('Andi');
		$relatedEntity = new TestEntity();
		$relatedEntity->setName('Robert');
		$entity->setRelatedEntity($relatedEntity);

		$this->testEntityRepository->add($entity);
		$this->testEntityRepository->add($relatedEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
		$loadedEntity = $this->testEntityRepository->findByIdentifier($entityIdentifier);

		$clonedRelatedEntity = clone $loadedEntity->getRelatedEntity();
		$this->testEntityRepository->add($clonedRelatedEntity);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$clonedEntityIdentifier = $this->persistenceManager->getIdentifierByObject($clonedRelatedEntity);
		$clonedLoadedEntity = $this->testEntityRepository->findByIdentifier($clonedEntityIdentifier);
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity', $clonedLoadedEntity);
	}

}
