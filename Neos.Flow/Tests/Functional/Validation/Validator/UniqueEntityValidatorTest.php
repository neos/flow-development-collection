<?php
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

use Neos\Flow\Tests\Functional\Persistence\Fixtures\Post;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;

/**
 * Testcase for the UniqueEntity Validator
 *
 */
class UniqueEntityValidatorTest extends \Neos\Flow\Tests\FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \Neos\Flow\Tests\Functional\Persistence\Fixtures\PostRepository
     */
    protected $postRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \Neos\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->postRepository = $this->objectManager->get(\Neos\Flow\Tests\Functional\Persistence\Fixtures\PostRepository::class);
    }

    /**
     * @test
     */
    public function validatorBehavesCorrectlyOnDuplicateEntityWithSingleConfiguredIdentityProperty()
    {
        $validator = new \Neos\Flow\Validation\Validator\UniqueEntityValidator(['identityProperties' => ['title']]);
        $post = new Post();
        $post->setTitle('The title of the initial post');
        $this->postRepository->add($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $differentPost = new Post();
        $differentPost->setTitle('A different title');
        $this->assertFalse($validator->validate($differentPost)->hasErrors());

        $nextPost = new Post();
        $nextPost->setTitle('The title of the initial post');
        $this->assertTrue($validator->validate($nextPost)->hasErrors());
    }

    /**
     * @test
     */
    public function validatorBehavesCorrectlyOnDuplicateEntityWithMultipleAnnotatedIdentityProperties()
    {
        $validator = new \Neos\Flow\Validation\Validator\UniqueEntityValidator();

        $book = new AnnotatedIdentitiesEntity();
        $book->setTitle('Watership Down');
        $book->setAuthor('Richard Adams');
        $this->persistenceManager->add($book);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $richardsOtherBook = new AnnotatedIdentitiesEntity();
        $richardsOtherBook->setTitle('The Plague Dogs');
        $richardsOtherBook->setAuthor('Richard Adams');
        $this->assertFalse($validator->validate($richardsOtherBook)->hasErrors());

        $otherWatershipDown = new AnnotatedIdentitiesEntity();
        $otherWatershipDown->setTitle('Watership Down');
        $otherWatershipDown->setAuthor('Martin Rosen');
        $this->assertFalse($validator->validate($otherWatershipDown)->hasErrors());

        $sameWatershipDown = new AnnotatedIdentitiesEntity();
        $sameWatershipDown->setTitle('Watership Down');
        $sameWatershipDown->setAuthor('Richard Adams');
        $this->assertTrue($validator->validate($sameWatershipDown)->hasErrors());
    }
}
