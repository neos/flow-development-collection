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

use TYPO3\Flow\Persistence\Doctrine\Query;

/**
 * Testcase for query
 *
 */
class QueryTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
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
	 * @test
	 */
	public function simpleQueryCanBeSerializedAndDeserialized() {
		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');
		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertQueryEquals($query, $unserializedQuery);
	}

	/**
	 * @test
	 */
	public function simpleQueryCanBeExecutedAfterDeserialization() {
		$testEntityRepository = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
		$testEntityRepository->removeAll();

		$testEntity1 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('Flow');
		$testEntityRepository->add($testEntity1);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');
		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertEquals(1, $unserializedQuery->execute()->count());
		$this->assertEquals(array($testEntity1), $unserializedQuery->execute()->toArray());
	}

	/**
	 * @test
	 */
	public function moreComplexQueryCanBeSerializedAndDeserialized() {
		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');
		$query->matching($query->equals('name', 'some'));

		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertQueryEquals($query, $unserializedQuery);
	}

	/**
	 * @test
	 */
	public function moreComplexQueryCanBeExecutedAfterDeserialization() {
		$testEntityRepository = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
		$testEntityRepository->removeAll();

		$testEntity1 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('Flow');
		$testEntityRepository->add($testEntity1);

		$testEntity2 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity2->setName('some');
		$testEntityRepository->add($testEntity2);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');
		$query->matching($query->equals('name', 'Flow'));

		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);
		$this->assertEquals(1, $unserializedQuery->execute()->count());
		$this->assertEquals(array($testEntity1), $unserializedQuery->execute()->toArray());
	}

	/**
	 * @test
	 */
	public function countIncludesAllResultsByDefault() {
		$testEntityRepository = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
		$testEntityRepository->removeAll();

		$testEntity1 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('Flow');
		$testEntityRepository->add($testEntity1);

		$testEntity2 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity2->setName('some');
		$testEntityRepository->add($testEntity2);

		$testEntity3 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity3->setName('more');
		$testEntityRepository->add($testEntity3);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');

		$this->assertEquals(3, $query->execute()->count());
	}

	/**
	 * @test
	 */
	public function countRespectsLimitConstraint() {
		$testEntityRepository = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
		$testEntityRepository->removeAll();

		$testEntity1 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('Flow');
		$testEntityRepository->add($testEntity1);

		$testEntity2 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity2->setName('some');
		$testEntityRepository->add($testEntity2);

		$testEntity3 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity3->setName('more');
		$testEntityRepository->add($testEntity3);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');

		$this->assertEquals(2, $query->setLimit(2)->execute()->count());
	}

	/**
	 * @test
	 */
	public function countRespectsOffsetConstraint() {
		$testEntityRepository = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
		$testEntityRepository->removeAll();

		$testEntity1 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('Flow');
		$testEntityRepository->add($testEntity1);

		$testEntity2 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity2->setName('some');
		$testEntityRepository->add($testEntity2);

		$testEntity3 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity3->setName('more');
		$testEntityRepository->add($testEntity3);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity');

		$this->assertEquals(1, $query->setOffset(2)->execute()->count());
	}

	/**
	 * @test
	 */
	public function comlexQueryWithJoinsCanBeExecutedAfterDeserialization() {
		$postEntityRepository = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\PostRepository;
		$postEntityRepository->removeAll();

		$commentRepository = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\CommentRepository;
		$commentRepository->removeAll();

		$testEntity1 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Post;
		$testEntity1->setTitle('Flow');
		$postEntityRepository->add($testEntity1);

		$testEntity2 = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Post;
		$testEntity2->setTitle('Flow with comment');
		$comment = new \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Comment;
		$comment->setContent('Flow');
		$testEntity2->setComment($comment);
		$postEntityRepository->add($testEntity2);
		$commentRepository->add($comment);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Post');
		$query->matching($query->equals('comment.content', 'Flow'));

		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);
		$this->assertEquals(1, $unserializedQuery->execute()->count());
		$this->assertEquals(array($testEntity2), $unserializedQuery->execute()->toArray());
	}

	protected function assertQueryEquals(Query $expected, Query $actual) {
		$this->assertEquals($expected->getConstraint(), $actual->getConstraint());
		$this->assertEquals($expected->getOrderings(), $actual->getOrderings());
		$this->assertEquals($expected->getOffset(), $actual->getOffset());
		$this->assertEquals($expected->getLimit(), $actual->getLimit());
	}

}
