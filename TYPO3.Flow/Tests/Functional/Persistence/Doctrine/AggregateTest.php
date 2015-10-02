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
        $this->postRepository = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\PostRepository');
        $this->commentRepository = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\CommentRepository');
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

        $retrievedImage = $this->persistenceManager->getObjectByIdentifier($imageIdentifier, 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image');
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

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Comment');
        $this->assertSame($comment, $retrievedComment);

        $this->postRepository->remove($post);
        $this->persistenceManager->persistAll();

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Comment');
        $this->assertSame($comment, $retrievedComment);
    }
}
