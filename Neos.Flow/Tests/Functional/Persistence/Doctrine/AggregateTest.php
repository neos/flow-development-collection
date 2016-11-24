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
 * Testcase for aggregate-related behavior
 */
class AggregateTest extends FunctionalTestCase
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
     * @var Fixtures\CommentRepository;
     */
    protected $commentRepository;

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
        $this->commentRepository = $this->objectManager->get(Fixtures\CommentRepository::class);
    }

    /**
     * @test
     */
    public function entitiesWithinAggregateAreRemovedAutomaticallyWithItsRootEntity()
    {
        $image = new Fixtures\Image();
        $post = new Fixtures\Post();
        $post->setImage($image);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();

        $imageIdentifier = $this->persistenceManager->getIdentifierByObject($image);

        $retrievedImage = $this->persistenceManager->getObjectByIdentifier($imageIdentifier, Fixtures\Image::class);
        $this->assertSame($image, $retrievedImage);

        $this->postRepository->remove($post);
        $this->persistenceManager->persistAll();

        $this->assertTrue($this->persistenceManager->isNewObject($retrievedImage));
    }

    /**
     * @test
     */
    public function entitiesWithOwnRepositoryAreNotRemovedIfRelatedRootEntityIsRemoved()
    {
        $comment = new Fixtures\Comment();
        $this->commentRepository->add($comment);

        $post = new Fixtures\Post();
        $post->setComment($comment);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();

        $commentIdentifier = $this->persistenceManager->getIdentifierByObject($comment);

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, Fixtures\Comment::class);
        $this->assertSame($comment, $retrievedComment);

        $this->postRepository->remove($post);
        $this->persistenceManager->persistAll();

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, Fixtures\Comment::class);
        $this->assertSame($comment, $retrievedComment);
    }

    /**
     * This test fixes FLOW-296 but is only affecting MySQL.
     *
     * @test
     */
    public function valueObjectsAreNotCascadeRemovedWhenARelatedEntityIsDeleted()
    {
        $post1 = new Fixtures\Post();
        $post1->setAuthor(new Fixtures\TestValueObject('Some Name'));

        $post2 = new Fixtures\Post();
        $post2->setAuthor(new Fixtures\TestValueObject('Some Name'));

        $this->postRepository->add($post1);
        $this->postRepository->add($post2);
        $this->persistenceManager->persistAll();

        $this->postRepository->remove($post1);
        $this->persistenceManager->persistAll();

        // if all goes well the value object is not deleted
        $this->assertTrue(true);
    }
}
