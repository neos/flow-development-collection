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

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Post;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Comment;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;

/**
 * Testcase for aggregate-related behavior
 */
class AggregateTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\PostRepository;
     */
    protected $postRepository;

    /**
     * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\CommentRepository;
     */
    protected $commentRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->postRepository = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\PostRepository::class);
        $this->commentRepository = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\CommentRepository::class);
    }

    /**
     * @test
     */
    public function entitiesWithinAggregateAreRemovedAutomaticallyWithItsRootEntity()
    {
        $image = new Image();
        $post = new Post();
        $post->setImage($image);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();

        $imageIdentifier = $this->persistenceManager->getIdentifierByObject($image);

        $retrievedImage = $this->persistenceManager->getObjectByIdentifier($imageIdentifier, \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image::class);
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
        $comment = new Comment();
        $this->commentRepository->add($comment);

        $post = new Post();
        $post->setComment($comment);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();

        $commentIdentifier = $this->persistenceManager->getIdentifierByObject($comment);

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Comment::class);
        $this->assertSame($comment, $retrievedComment);

        $this->postRepository->remove($post);
        $this->persistenceManager->persistAll();

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Comment::class);
        $this->assertSame($comment, $retrievedComment);
    }

    /**
     * This test fixes FLOW-296 but is only affecting MySQL.
     *
     * @test
     */
    public function valueObjectsAreNotCascadeRemovedWhenARelatedEntityIsDeleted()
    {
        $post1 = new Post();
        $post1->setAuthor(new TestValueObject('Some Name'));

        $post2 = new Post();
        $post2->setAuthor(new TestValueObject('Some Name'));

        $this->postRepository->add($post1);
        $this->postRepository->add($post2);
        $this->persistenceManager->persistAll();

        $this->postRepository->remove($post1);
        $this->persistenceManager->persistAll();

        // if all goes well the value object is not deleted
        $this->assertTrue(true);
    }
}
