<?php
namespace TYPO3\Flow\Tests\Functional\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Post,
	TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;

/**
 * Testcase for the UniqueEntity Validator
 *
 */
class UniqueEntityValidatorTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\PostRepository
	 */
	protected $postRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}

		$this->postRepository = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\PostRepository');
	}

	/**
	 * @test
	 */
	public function validatorBehavesCorrectlyOnDuplicateEntityWithSingleConfiguredIdentityProperty() {
		$validator = new \TYPO3\Flow\Validation\Validator\UniqueEntityValidator(array('identityProperties' => array('title')));
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
	public function validatorBehavesCorrectlyOnDuplicateEntityWithMultipleAnnotatedIdentityProperties() {
		$validator = new \TYPO3\Flow\Validation\Validator\UniqueEntityValidator();

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
?>