<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Validation\Validator;

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
use Neos\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Post;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\PostRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Validation\Validator\UniqueEntityValidator;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the UniqueEntity Validator
 *
 */
final class UniqueEntityValidatorTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var PostRepository
     */
    protected $postRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->postRepository = $this->objectManager->get(PostRepository::class);
    }

    #[Test]
    public function validatorBehavesCorrectlyOnDuplicateEntityWithSingleConfiguredIdentityProperty()
    {
        $validator = new UniqueEntityValidator(['identityProperties' => ['title']]);
        $post = new Post();
        $post->setTitle('The title of the initial post');
        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $differentPost = new Post();
        $differentPost->setTitle('A different title');
        self::assertFalse($validator->validate($differentPost)->hasErrors());

        $nextPost = new Post();
        $nextPost->setTitle('The title of the initial post');
        self::assertTrue($validator->validate($nextPost)->hasErrors());
    }

    #[Test]
    public function validatorBehavesCorrectlyOnDuplicateEntityWithMultipleAnnotatedIdentityProperties()
    {
        $validator = new UniqueEntityValidator();

        $book = new AnnotatedIdentitiesEntity();
        $book->setTitle('Watership Down');
        $book->setAuthor('Richard Adams');
        $this->persistenceManager->add($book);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $richardsOtherBook = new AnnotatedIdentitiesEntity();
        $richardsOtherBook->setTitle('The Plague Dogs');
        $richardsOtherBook->setAuthor('Richard Adams');
        self::assertFalse($validator->validate($richardsOtherBook)->hasErrors());

        $otherWatershipDown = new AnnotatedIdentitiesEntity();
        $otherWatershipDown->setTitle('Watership Down');
        $otherWatershipDown->setAuthor('Martin Rosen');
        self::assertFalse($validator->validate($otherWatershipDown)->hasErrors());

        $sameWatershipDown = new AnnotatedIdentitiesEntity();
        $sameWatershipDown->setTitle('Watership Down');
        $sameWatershipDown->setAuthor('Richard Adams');
        self::assertTrue($validator->validate($sameWatershipDown)->hasErrors());
    }
}
