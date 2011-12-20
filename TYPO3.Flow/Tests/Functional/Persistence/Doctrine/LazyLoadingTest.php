<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;

/**
 * Testcase for proxy initialization within doctrine lazy loading
 */
class LazyLoadingTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository;
	 */
	protected $testEntityRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->testEntityRepository = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository');
	}

	/**
	 * @test
	 */
	public function dependencyInjectionIsCorrectlyInitializedEvenIfADoctrineProxyGetsInitializedOnTheFlyFromTheOutside() {
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

		$this->testEntityRepository->findOneByName('Robert');

		$loadedRelatedEntity = $loadedEntity->getRelatedEntity();

		$this->assertNotNull($loadedRelatedEntity->getObjectManager());
	}

	/**
	 * @test
	 */
	public function aopIsCorrectlyInitializedEvenIfADoctrineProxyGetsInitializedOnTheFlyFromTheOutside() {
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

		$this->testEntityRepository->findOneByName('Robert');

		$loadedRelatedEntity = $loadedEntity->getRelatedEntity();

		$this->assertEquals($loadedRelatedEntity->sayHello(), 'Hello Andi!');
	}
}
?>