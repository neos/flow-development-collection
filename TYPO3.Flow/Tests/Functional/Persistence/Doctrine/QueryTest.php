<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Doctrine;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Persistence\Doctrine\Query;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for query
 *
 */
class QueryTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    /**
     * @test
     */
    public function simpleQueryCanBeSerializedAndDeserialized()
    {
        $query = new Query(Fixtures\TestEntity::class);
        $serializedQuery = serialize($query);
        $unserializedQuery = unserialize($serializedQuery);

        $this->assertQueryEquals($query, $unserializedQuery);
    }

    /**
     * @test
     */
    public function simpleQueryCanBeExecutedAfterDeserialization()
    {
        $testEntityRepository = new Fixtures\TestEntityRepository();
        $testEntityRepository->removeAll();

        $testEntity1 = new Fixtures\TestEntity();
        $testEntity1->setName('Flow');
        $testEntityRepository->add($testEntity1);

        $this->persistenceManager->persistAll();

        $query = new Query(Fixtures\TestEntity::class);
        $serializedQuery = serialize($query);
        $unserializedQuery = unserialize($serializedQuery);

        $this->assertEquals(1, $unserializedQuery->execute()->count());
        $this->assertEquals([$testEntity1], $unserializedQuery->execute()->toArray());
    }

    /**
     * @test
     */
    public function moreComplexQueryCanBeSerializedAndDeserialized()
    {
        $query = new Query(Fixtures\TestEntity::class);
        $query->matching($query->equals('name', 'some'));

        $serializedQuery = serialize($query);
        $unserializedQuery = unserialize($serializedQuery);

        $this->assertQueryEquals($query, $unserializedQuery);
    }

    /**
     * @test
     */
    public function moreComplexQueryCanBeExecutedAfterDeserialization()
    {
        $testEntityRepository = new Fixtures\TestEntityRepository();
        $testEntityRepository->removeAll();

        $testEntity1 = new Fixtures\TestEntity();
        $testEntity1->setName('Flow');
        $testEntityRepository->add($testEntity1);

        $testEntity2 = new Fixtures\TestEntity();
        $testEntity2->setName('some');
        $testEntityRepository->add($testEntity2);

        $this->persistenceManager->persistAll();

        $query = new Query(Fixtures\TestEntity::class);
        $query->matching($query->equals('name', 'Flow'));

        $serializedQuery = serialize($query);
        $unserializedQuery = unserialize($serializedQuery);
        $this->assertEquals(1, $unserializedQuery->execute()->count());
        $this->assertEquals([$testEntity1], $unserializedQuery->execute()->toArray());
    }

    /**
     * @test
     */
    public function countIncludesAllResultsByDefault()
    {
        $testEntityRepository = new Fixtures\TestEntityRepository();
        $testEntityRepository->removeAll();

        $testEntity1 = new Fixtures\TestEntity();
        $testEntity1->setName('Flow');
        $testEntityRepository->add($testEntity1);

        $testEntity2 = new Fixtures\TestEntity();
        $testEntity2->setName('some');
        $testEntityRepository->add($testEntity2);

        $testEntity3 = new Fixtures\TestEntity();
        $testEntity3->setName('more');
        $testEntityRepository->add($testEntity3);

        $this->persistenceManager->persistAll();

        $query = new Query(Fixtures\TestEntity::class);

        $this->assertEquals(3, $query->execute()->count());
    }

    /**
     * @test
     */
    public function countRespectsLimitConstraint()
    {
        $testEntityRepository = new Fixtures\TestEntityRepository();
        $testEntityRepository->removeAll();

        $testEntity1 = new Fixtures\TestEntity();
        $testEntity1->setName('Flow');
        $testEntityRepository->add($testEntity1);

        $testEntity2 = new Fixtures\TestEntity();
        $testEntity2->setName('some');
        $testEntityRepository->add($testEntity2);

        $testEntity3 = new Fixtures\TestEntity();
        $testEntity3->setName('more');
        $testEntityRepository->add($testEntity3);

        $this->persistenceManager->persistAll();

        $query = new Query(Fixtures\TestEntity::class);

        $this->assertEquals(2, $query->setLimit(2)->execute()->count());
    }

    /**
     * @test
     */
    public function countRespectsOffsetConstraint()
    {
        $testEntityRepository = new Fixtures\TestEntityRepository();
        $testEntityRepository->removeAll();

        $testEntity1 = new Fixtures\TestEntity();
        $testEntity1->setName('Flow');
        $testEntityRepository->add($testEntity1);

        $testEntity2 = new Fixtures\TestEntity();
        $testEntity2->setName('some');
        $testEntityRepository->add($testEntity2);

        $testEntity3 = new Fixtures\TestEntity();
        $testEntity3->setName('more');
        $testEntityRepository->add($testEntity3);

        $this->persistenceManager->persistAll();

        $query = new Query(Fixtures\TestEntity::class);

        $this->assertEquals(1, $query->setOffset(2)->execute()->count());
    }

    /**
     * @test
     */
    public function distinctQueryOnlyReturnsDistinctEntities()
    {
        $testEntityRepository = new Fixtures\TestEntityRepository();
        $testEntityRepository->removeAll();

        $testEntity = new Fixtures\TestEntity();
        $testEntity->setName('Flow');

        $subEntity1 = new Fixtures\SubEntity();
        $subEntity1->setContent('value');
        $subEntity1->setParentEntity($testEntity);
        $testEntity->addSubEntity($subEntity1);
        $this->persistenceManager->add($subEntity1);

        $subEntity2 = new Fixtures\SubEntity();
        $subEntity2->setContent('value');
        $subEntity2->setParentEntity($testEntity);
        $testEntity->addSubEntity($subEntity2);
        $this->persistenceManager->add($subEntity2);

        $testEntityRepository->add($testEntity);

        $testEntity2 = new Fixtures\TestEntity();
        $testEntity2->setName('Flow');

        $subEntity3 = new Fixtures\SubEntity();
        $subEntity3->setContent('value');
        $subEntity3->setParentEntity($testEntity2);
        $testEntity2->addSubEntity($subEntity3);
        $this->persistenceManager->add($subEntity3);

        $testEntityRepository->add($testEntity2);

        $this->persistenceManager->persistAll();

        $query = new Query(Fixtures\TestEntity::class);
        $entities = $query->matching($query->equals('subEntities.content', 'value'))->setDistinct()->setLimit(2)->execute()->toArray();

        $this->assertEquals(2, count($entities));
    }

    /**
     * @test
     */
    public function comlexQueryWithJoinsCanBeExecutedAfterDeserialization()
    {
        $postEntityRepository = new Fixtures\PostRepository();
        $postEntityRepository->removeAll();

        $commentRepository = new Fixtures\CommentRepository();
        $commentRepository->removeAll();

        $testEntity1 = new Fixtures\Post();
        $testEntity1->setTitle('Flow');
        $postEntityRepository->add($testEntity1);

        $testEntity2 = new Fixtures\Post();
        $testEntity2->setTitle('Flow with comment');
        $comment = new Fixtures\Comment();
        $comment->setContent('Flow');
        $testEntity2->setComment($comment);
        $postEntityRepository->add($testEntity2);
        $commentRepository->add($comment);

        $this->persistenceManager->persistAll();

        $query = new Query(Fixtures\Post::class);
        $query->matching($query->equals('comment.content', 'Flow'));

        $serializedQuery = serialize($query);
        $unserializedQuery = unserialize($serializedQuery);
        $this->assertEquals(1, $unserializedQuery->execute()->count());
        $this->assertEquals([$testEntity2], $unserializedQuery->execute()->toArray());
    }

    protected function assertQueryEquals(Query $expected, Query $actual)
    {
        $this->assertEquals($expected->getConstraint(), $actual->getConstraint());
        $this->assertEquals($expected->getOrderings(), $actual->getOrderings());
        $this->assertEquals($expected->getOffset(), $actual->getOffset());
        $this->assertEquals($expected->getLimit(), $actual->getLimit());
    }
}
