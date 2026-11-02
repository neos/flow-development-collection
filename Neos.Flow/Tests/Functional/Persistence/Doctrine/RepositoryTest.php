<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Post;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\PostRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\SubEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\SubSubEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\SubSubEntityRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\SuperEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\SuperEntityRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for basic repository operations
 */
final class RepositoryTest extends FunctionalTestCase
{
    /**
     * @var bool
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Fixtures\PostRepository;
     */
    protected $postRepository;

    /**
     * @var Fixtures\SuperEntityRepository;
     */
    protected $superEntityRepository;

    /**
     * @var Fixtures\SubSubEntityRepository;
     */
    protected $subSubEntityRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            static::markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    #[Test]
    public function modificationsOnRetrievedEntitiesAreNotPersistedAutomatically(): void
    {
        $this->postRepository = $this->objectManager->get(PostRepository::class);

        $post = new Post();
        $post->setTitle('Sample');
        $this->postRepository->add($post);

        $this->persistenceManager->persistAll();
        unset($post);

        $post = $this->postRepository->findOneByTitle('Sample');
        $post->setTitle('Modified Sample');

        $this->persistenceManager->persistAll();
        unset($post);

        $post = $this->postRepository->findOneByTitle('Modified Sample');
        self::assertNull($post);

        // The following assertions won't work because findOneByTitle() will get the _modified_ post
        // because it is still in Doctrine's identity map:

        // $post = $this->postRepository->findOneByTitle('Sample');
        // self::assertNotNull($post);
        // self::assertEquals('Sample', $post->getTitle());
    }

    #[Test]
    public function modificationsOnRetrievedEntitiesArePersistedIfUpdateHasBeenCalled(): void
    {
        $this->postRepository = $this->objectManager->get(PostRepository::class);

        $post = new Post();
        $post->setTitle('Sample');
        $this->postRepository->add($post);

        $this->persistenceManager->persistAll();

        $post = $this->postRepository->findOneByTitle('Sample');
        $post->setTitle('Modified Sample');
        $this->postRepository->update($post);

        $this->persistenceManager->persistAll();

        $post = $this->postRepository->findOneByTitle('Modified Sample');
        self::assertNotNull($post);
        self::assertEquals('Modified Sample', $post->getTitle());
    }

    #[Test]
    public function instancesOfTheManagedTypeCanBeAddedAndRetrieved(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $superEntity = new SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $this->persistenceManager->persistAll();

        $superEntity = $this->superEntityRepository->findOneByContent('this is the super entity');
        self::assertEquals('this is the super entity', $superEntity->getContent());
    }

    #[Test]
    public function subTypesOfTheManagedTypeCanBeAddedAndRetrieved(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $subEntity = new SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('this is the sub entity');
        self::assertEquals('this is the sub entity', $subEntity->getContent());
    }

    #[Test]
    public function subTypesOfTheManagedTypeCanBeRemoved(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $subEntity = new SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('this is the sub entity');
        $this->superEntityRepository->remove($subEntity);
        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('this is the sub entity');
        self::assertNull($subEntity);
    }

    #[Test]
    public function subTypesOfTheManagedTypeCanBeUpdated(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $subEntity = new SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('this is the sub entity');
        $subEntity->setContent('updated sub entity content');
        $this->superEntityRepository->update($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('updated sub entity content');
        self::assertNotNull($subEntity);
        self::assertEquals('updated sub entity content', $subEntity->getContent());
    }

    #[Test]
    public function countAllCountsSubTypesOfTheManagedType(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $superEntity = new SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $subEntity = new SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        self::assertEquals(2, $this->superEntityRepository->countAll());
    }

    #[Test]
    public function findAllReturnsSubTypesOfTheManagedType(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $superEntity = new SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $subEntity = new SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        self::assertEquals(2, $this->superEntityRepository->findAll()->count());
    }

    #[Test]
    public function findAllIteratorReturnsSubTypesOfTheManagedType(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $superEntity = new SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $subEntity = new SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        $iterator = $this->superEntityRepository->findAllIterator();
        $expectedCount = 0;

        foreach ($iterator as $entity) {
            $expectedCount++;
        }

        self::assertSame(2, $expectedCount);
    }

    #[Test]
    public function findByIdentifierReturnsSubTypesOfTheManagedType(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);

        $subEntity = new SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);
        $identifier = $this->persistenceManager->getIdentifierByObject($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findByIdentifier($identifier);
        self::assertEquals('this is the sub entity', $subEntity->getContent());
    }

    #[Test]
    public function addingASuperTypeToAMoreSpecificRepositoryThrowsAnException(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->subSubEntityRepository = $this->objectManager->get(SubSubEntityRepository::class);

        $subEntity = new SubEntity();
        $this->subSubEntityRepository->add($subEntity);
    }

    #[Test]
    public function usingASpecificRepositoryForSubTypesWorks(): void
    {
        $this->superEntityRepository = $this->objectManager->get(SuperEntityRepository::class);
        $this->subSubEntityRepository = $this->objectManager->get(SubSubEntityRepository::class);

        $subSubEntity = new SubSubEntity();
        $subSubEntity->setContent('this is the sub sub entity');
        $this->superEntityRepository->add($subSubEntity);

        $this->persistenceManager->persistAll();

        $subSubEntity = $this->superEntityRepository->findAll()->getFirst();
        self::assertEquals('this is the sub sub entity', $subSubEntity->getContent());

        $subSubEntity = $this->subSubEntityRepository->findAll()->getFirst();
        self::assertEquals('this is the sub sub entity - touched by SubSubEntityRepository', $subSubEntity->getContent());
    }

    #[Test]
    public function findAllReturnsQueryResult(): void
    {
        $this->postRepository = $this->objectManager->get(PostRepository::class);
        self::assertInstanceOf(Repository::class, $this->postRepository, 'Repository under test should be a Doctrine Repository');

        $result = $this->postRepository->findAll();
        self::assertInstanceOf(QueryResultInterface::class, $result, 'findAll should return a QueryResult object');
    }
}
