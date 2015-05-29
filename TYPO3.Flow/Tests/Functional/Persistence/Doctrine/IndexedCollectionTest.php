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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\EntityWithIndexedRelation;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\RelatedIndexEntity;

/**
 * Test for Doctrine indexed Collections
 * @Flow\Scope("prototype")
 */
class IndexedCollectionTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
	}

	/**
	 * This tests calls two indexed Relations and ensure that indexes are restored after fetching from persistence
	 *
	 * @test
	 */
	public function collectionsWithIndexAttributeAreIndexed() {

		$entityWithIndexedRelation = new EntityWithIndexedRelation();
		for($i = 0; $i < 3; $i++) {
			$annotatedIdentitiesEntity = new AnnotatedIdentitiesEntity();
			$annotatedIdentitiesEntity->setAuthor('Author' . ((string) $i));
			$annotatedIdentitiesEntity->setTitle('Author' . ((string) $i));
			$entityWithIndexedRelation->getAnnotatedIdentitiesEntities()->add($annotatedIdentitiesEntity);
		}

		$entityWithIndexedRelation->setRelatedIndexEntity('test', new RelatedIndexEntity());

		$this->persistenceManager->add($entityWithIndexedRelation);
		$this->persistenceManager->persistAll();
		$id = $this->persistenceManager->getIdentifierByObject($entityWithIndexedRelation);

		//Reset persistence manager to make sure fresh instances will be created
		$this->persistenceManager->clearState();
		unset($entityWithIndexedRelation);

		$entityWithIndexedRelation = $this->persistenceManager->getObjectByIdentifier($id, 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\EntityWithIndexedRelation');
		for ($i = 0; $i < 3; $i++) {
			$this->assertArrayHasKey('Author' . (string) $i, $entityWithIndexedRelation->getAnnotatedIdentitiesEntities());
		}
		$this->assertArrayNotHasKey(0, $entityWithIndexedRelation->getAnnotatedIdentitiesEntities());

		$this->assertArrayHasKey('test', $entityWithIndexedRelation->getRelatedIndexEntities());
		$this->assertArrayNotHasKey(0, $entityWithIndexedRelation->getRelatedIndexEntities());
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\RelatedIndexEntity', $entityWithIndexedRelation->getRelatedIndexEntities()->get('test'));

	}

}
