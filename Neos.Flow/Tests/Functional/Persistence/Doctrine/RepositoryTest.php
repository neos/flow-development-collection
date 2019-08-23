<?php
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
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for basic repository operations
 */
class RepositoryTest extends FunctionalTestCase
{
    /**
     * @var boolean
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
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    /**
     * @test
     */
    public function modificationsOnRetrievedEntitiesAreNotPersistedAutomatically()
    {
        $this->postRepository = $this->objectManager->get(Fixtures\PostRepository::class);

        $post = new Fixtures\Post();
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

    /**
     * @test
     */
    public function modificationsOnRetrievedEntitiesArePersistedIfUpdateHasBeenCalled()
    {
        $this->postRepository = $this->objectManager->get(Fixtures\PostRepository::class);

        $post = new Fixtures\Post();
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

    /**
     * @test
     */
    public function instancesOfTheManagedTypeCanBeAddedAndRetrieved()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $superEntity = new Fixtures\SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $this->persistenceManager->persistAll();

        $superEntity = $this->superEntityRepository->findOneByContent('this is the super entity');
        self::assertEquals('this is the super entity', $superEntity->getContent());
    }

    /**
     * @test
     */
    public function subTypesOfTheManagedTypeCanBeAddedAndRetrieved()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $subEntity = new Fixtures\SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('this is the sub entity');
        self::assertEquals('this is the sub entity', $subEntity->getContent());
    }

    /**
     * @test
     */
    public function subTypesOfTheManagedTypeCanBeRemoved()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $subEntity = new Fixtures\SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('this is the sub entity');
        $this->superEntityRepository->remove($subEntity);
        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findOneByContent('this is the sub entity');
        self::assertNull($subEntity);
    }

    /**
     * @test
     */
    public function subTypesOfTheManagedTypeCanBeUpdated()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $subEntity = new Fixtures\SubEntity();
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

    /**
     * @test
     */
    public function countAllCountsSubTypesOfTheManagedType()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $superEntity = new Fixtures\SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $subEntity = new Fixtures\SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        self::assertEquals(2, $this->superEntityRepository->countAll());
    }

    /**
     * @test
     */
    public function findAllReturnsSubTypesOfTheManagedType()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $superEntity = new Fixtures\SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $subEntity = new Fixtures\SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        self::assertEquals(2, $this->superEntityRepository->findAll()->count());
    }

    /**
     * @test
     */
    public function findAllIteratorReturnsSubTypesOfTheManagedType()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $superEntity = new Fixtures\SuperEntity();
        $superEntity->setContent('this is the super entity');
        $this->superEntityRepository->add($superEntity);

        $subEntity = new Fixtures\SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);

        $this->persistenceManager->persistAll();

        $iterator = $this->superEntityRepository->findAllIterator();
        $expectedCount = 0;

        foreach ($this->superEntityRepository->iterate($iterator) as $entity) {
            $expectedCount++;
        }

        self::assertEquals(2, $expectedCount);
    }

    /**
     * @test
     */
    public function findByIdentifierReturnsSubTypesOfTheManagedType()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);

        $subEntity = new Fixtures\SubEntity();
        $subEntity->setContent('this is the sub entity');
        $this->superEntityRepository->add($subEntity);
        $identifier = $this->persistenceManager->getIdentifierByObject($subEntity);

        $this->persistenceManager->persistAll();

        $subEntity = $this->superEntityRepository->findByIdentifier($identifier);
        self::assertEquals('this is the sub entity', $subEntity->getContent());
    }

    /**
     * @test
     */
    public function addingASuperTypeToAMoreSpecificRepositoryThrowsAnException()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->subSubEntityRepository = $this->objectManager->get(Fixtures\SubSubEntityRepository::class);

        $subEntity = new Fixtures\SubEntity();
        $this->subSubEntityRepository->add($subEntity);
    }

    /**
     * @test
     */
    public function usingASpecificRepositoryForSubTypesWorks()
    {
        $this->superEntityRepository = $this->objectManager->get(Fixtures\SuperEntityRepository::class);
        $this->subSubEntityRepository = $this->objectManager->get(Fixtures\SubSubEntityRepository::class);

        $subSubEntity = new Fixtures\SubSubEntity();
        $subSubEntity->setContent('this is the sub sub entity');
        $this->superEntityRepository->add($subSubEntity);

        $this->persistenceManager->persistAll();

        $subSubEntity = $this->superEntityRepository->findAll()->getFirst();
        self::assertEquals('this is the sub sub entity', $subSubEntity->getContent());

        $subSubEntity = $this->subSubEntityRepository->findAll()->getFirst();
        self::assertEquals('this is the sub sub entity - touched by SubSubEntityRepository', $subSubEntity->getContent());
    }

    /**
     * @test
     */
    public function findAllReturnsQueryResult()
    {
        $this->postRepository = $this->objectManager->get(Fixtures\PostRepository::class);
        self::assertInstanceOf(Repository::class, $this->postRepository, 'Repository under test should be a Doctrine Repository');

        $result = $this->postRepository->findAll();
        self::assertInstanceOf(QueryResultInterface::class, $result, 'findAll should return a QueryResult object');
    }
}
