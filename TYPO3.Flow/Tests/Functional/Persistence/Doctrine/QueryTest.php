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

use TYPO3\FLOW3\Persistence\Doctrine\Query;

/**
 * Testcase for query
 *
 */
class QueryTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
	}

	/**
	 * @test
	 */
	public function simpleQueryCanBeSerializedAndDeserialized() {
		$query = new Query('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertQueryEquals($query, $unserializedQuery);
	}

	/**
	 * @test
	 */
	public function simpleQueryCanBeExecutedAfterDeserialization() {
		$testEntityRepository = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
		$testEntityRepository->removeAll();

		$testEntity1 = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('FLOW3');
		$testEntityRepository->add($testEntity1);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertEquals(1, $unserializedQuery->execute()->count());
		$this->assertEquals(array($testEntity1), $unserializedQuery->execute()->toArray());
	}

	/**
	 * @test
	 */
	public function moreComplexQueryCanBeSerializedAndDeserialized() {
		$query = new Query('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$query->matching($query->equals('name', 'some'));

		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);

		$this->assertQueryEquals($query, $unserializedQuery);
	}

	/**
	 * @test
	 */
	public function moreComplexQueryCanBeExecutedAfterDeserialization() {
		$testEntityRepository = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntityRepository();
		$testEntityRepository->removeAll();

		$testEntity1 = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity1->setName('FLOW3');
		$testEntityRepository->add($testEntity1);

		$testEntity2 = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity;
		$testEntity2->setName('some');
		$testEntityRepository->add($testEntity1);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity');
		$query->matching($query->equals('name', 'FLOW3'));

		$serializedQuery = serialize($query);
		$unserializedQuery = unserialize($serializedQuery);
		$this->assertEquals(1, $unserializedQuery->execute()->count());
		$this->assertEquals(array($testEntity1), $unserializedQuery->execute()->toArray());
	}

	/**
	 * @test
	 */
	public function comlexQueryWithJoinsCanBeExecutedAfterDeserialization() {
		$postEntityRepository = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\PostRepository;
		$postEntityRepository->removeAll();

		$commentRepository = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\CommentRepository;
		$commentRepository->removeAll();

		$testEntity1 = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post;
		$testEntity1->setTitle('FLOW3');
		$postEntityRepository->add($testEntity1);

		$testEntity2 = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post;
		$testEntity2->setTitle('FLOW3 with comment');
		$comment = new \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Comment;
		$comment->setContent('FLOW3');
		$testEntity2->setComment($comment);
		$postEntityRepository->add($testEntity2);
		$commentRepository->add($comment);

		$this->persistenceManager->persistAll();

		$query = new Query('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post');
		$query->matching($query->equals('comment.content', 'FLOW3'));

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
?>