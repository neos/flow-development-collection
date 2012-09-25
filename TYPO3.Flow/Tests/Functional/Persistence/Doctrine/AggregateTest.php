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

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Post;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\PostRepository;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Comment;

/**
 * Testcase for aggregate-related behavior
 */
class AggregateTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

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
	public function setUp() {
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
	public function entitiesWithinAggregateAreRemovedAutomaticallyWithItsRootEntity() {
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
	public function entitiesWithOwnRepositoryAreNotRemovedIfRelatedRootEntityIsRemoved() {
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
?>