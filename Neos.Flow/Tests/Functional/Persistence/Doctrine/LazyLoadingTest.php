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
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for proxy initialization within doctrine lazy loading
 */
class LazyLoadingTest extends FunctionalTestCase
{
    /**
     * @var boolean
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
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->postRepository = $this->objectManager->get(Fixtures\PostRepository::class);
        $this->testEntityRepository = $this->objectManager->get(Fixtures\TestEntityRepository::class);
    }

    /**
     * @test
     */
    public function dependencyInjectionIsCorrectlyInitializedEvenIfADoctrineProxyGetsInitializedOnTheFlyFromTheOutside()
    {
        $entity = new Fixtures\TestEntity();
        $entity->setName('Andi');
        $relatedEntity = new Fixtures\TestEntity();
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
    public function aopIsCorrectlyInitializedEvenIfADoctrineProxyGetsInitializedOnTheFlyFromTheOutside()
    {
        $entity = new Fixtures\TestEntity();
        $entity->setName('Andi');
        $relatedEntity = new Fixtures\TestEntity();
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

    /**
     * @test
     */
    public function shutdownObjectMethodIsRegisterdForDoctrineProxy()
    {
        $image = new Fixtures\Image();
        $post = new Fixtures\Post();
        $post->setImage($image);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $postIdentifier = $this->persistenceManager->getIdentifierByObject($post);

        unset($post);
        unset($image);

        /*
         * When hydrating the post a DoctrineProxy is generated for the image.
         * On this proxy __wakeup() is called and the shutdownObject lifecycle method
         * needs to be registered in the ObjectManager
         */
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, Fixtures\Post::class);

        /*
         * The CleanupObject is just a helper object to test that shutdownObject() on the Fixtures\Image is called
         */
        $cleanupObject = new Fixtures\CleanupObject();
        $this->assertFalse($cleanupObject->getState());
        $post->getImage()->setRelatedObject($cleanupObject);

        /*
         * When shutting down the ObjectManager shutdownObject() on Fixtures\Image is called
         * and toggles the state on the cleanupObject
         */
        \Neos\Flow\Core\Bootstrap::$staticObjectManager->shutdown();

        $this->assertTrue($cleanupObject->getState());
    }
}
