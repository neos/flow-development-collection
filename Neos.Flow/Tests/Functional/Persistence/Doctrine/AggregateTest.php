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
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Comment;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\CommentRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Image;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Post;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\PostRepository;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Tag;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for aggregate-related behavior
 */
final class AggregateTest extends FunctionalTestCase
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
     * @var Fixtures\CommentRepository;
     */
    protected $commentRepository;

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
        $this->commentRepository = $this->objectManager->get(CommentRepository::class);
    }

    #[Test]
    public function entitiesWithinAggregateAreRemovedAutomaticallyWithItsRootEntity(): void
    {
        $image = new Image();
        $post = new Post();
        $post->setImage($image);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();

        $imageIdentifier = $this->persistenceManager->getIdentifierByObject($image);

        $retrievedImage = $this->persistenceManager->getObjectByIdentifier($imageIdentifier, Image::class);
        self::assertSame($image, $retrievedImage);

        $this->postRepository->remove($post);
        $this->persistenceManager->persistAll();

        self::assertTrue($this->persistenceManager->isNewObject($retrievedImage));
    }

    #[Test]
    public function entitiesWithOwnRepositoryAreNotRemovedIfRelatedRootEntityIsRemoved(): void
    {
        $comment = new Comment();
        $this->commentRepository->add($comment);

        $post = new Post();
        $post->setComment($comment);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();

        $commentIdentifier = $this->persistenceManager->getIdentifierByObject($comment);

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, Comment::class);
        self::assertSame($comment, $retrievedComment);

        $this->postRepository->remove($post);
        $this->persistenceManager->persistAll();

        $retrievedComment = $this->persistenceManager->getObjectByIdentifier($commentIdentifier, Comment::class);
        self::assertSame($comment, $retrievedComment);
    }

    /**
     * This test fixes FLOW-296 but is only affecting MySQL.
     */
    #[Test]
    public function valueObjectsAreNotCascadeRemovedWhenARelatedEntityIsDeleted(): void
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
        self::assertTrue(true);
    }

    #[Test]
    public function unidirectionalOneToManyRelationsAreMapped(): void
    {
        $tag1 = new Tag('Tag1');
        $tag2 = new Tag('Tag2');
        $post = new Post();
        $post->addTag($tag1);
        $post->addTag($tag2);

        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();

        $postIdentifier = $this->persistenceManager->getIdentifierByObject($post);
        $tag1identifier = $this->persistenceManager->getIdentifierByObject($tag1);
        $tag2identifier = $this->persistenceManager->getIdentifierByObject($tag2);

        $retrievedTag1 = $this->persistenceManager->getObjectByIdentifier($tag1identifier, Tag::class);
        self::assertSame($tag1, $retrievedTag1, 'Tag not persisted');

        $post->removeTag($tag2);
        $this->postRepository->update($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $retrievedTag2 = $this->persistenceManager->getObjectByIdentifier($tag2identifier, Tag::class);
        self::assertNull($retrievedTag2, 'Tag not deleted');

        $post = $this->postRepository->find($postIdentifier);
        $this->postRepository->remove($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $retrievedTag1 = $this->persistenceManager->getObjectByIdentifier($tag1identifier, Tag::class);
        self::assertNull($retrievedTag1, 'Tag not orphan removed');
    }
}
