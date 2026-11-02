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
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\CleanupObject;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Image;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Post;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\PostRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for proxy initialization within doctrine lazy loading
 */
final class LazyLoadingTest extends FunctionalTestCase
{
    /**
     * @var bool
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Fixtures\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @var Fixtures\PostRepository
     */
    protected $postRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            static::markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->postRepository = $this->objectManager->get(PostRepository::class);
        $this->testEntityRepository = $this->objectManager->get(TestEntityRepository::class);
    }

    #[Test]
    public function dependencyInjectionIsCorrectlyInitializedEvenIfADoctrineProxyGetsInitializedOnTheFlyFromTheOutside(): void
    {
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

        self::assertNotNull($loadedRelatedEntity->getObjectManager());
    }

    #[Test]
    public function aopIsCorrectlyInitializedEvenIfADoctrineProxyGetsInitializedOnTheFlyFromTheOutside(): void
    {
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

        self::assertEquals('Hello Andi!', $loadedRelatedEntity->sayHello());
    }

    #[Test]
    public function shutdownObjectMethodIsRegisteredForDoctrineProxy(): void
    {
        $image = new Image();
        $post = new Post();
        $post->setImage($image);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $postIdentifier = $this->persistenceManager->getIdentifierByObject($post);

        unset($post, $image);

        /*
         * When hydrating the post a DoctrineProxy is generated for the image.
         * On this proxy __wakeup() is called and the shutdownObject lifecycle method
         * needs to be registered in the ObjectManager
         */
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, Post::class);

        /*
         * The CleanupObject is just a helper object to test that shutdownObject() on the Fixtures\Image is called
         */
        $cleanupObject = new CleanupObject();
        self::assertFalse($cleanupObject->getState());
        $post->getImage()->setRelatedObject($cleanupObject);

        /*
         * When shutting down the ObjectManager shutdownObject() on Fixtures\Image is called
         * and toggles the state on the cleanupObject
         */
        Bootstrap::$staticObjectManager->shutdown();

        self::assertTrue($cleanupObject->getState());
    }
}
