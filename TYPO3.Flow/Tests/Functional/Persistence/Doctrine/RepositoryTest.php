<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post;
use \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\PostRepository;

/**
 * Testcase for basic repository operations
 */
class RepositoryTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\PostRepository;
	 */
	protected $postRepository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->postRepository = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\PostRepository');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modificationsOnRetrievedEntitiesAreNotPersistedAutomatically() {
		$post = new Post();
		$post->setTitle('Sample');
		$this->postRepository->add($post);

		$this->persistenceManager->persistAll();
		unset($post);

		$post = $this->postRepository->findOneByTitle('Sample');
		$post->setTitle('Modified Sample');

		$this->persistenceManager->persistAll();
		unset($post);

		$post = $this->postRepository->findOneByTitle('Modified Sample');
		$this->assertNull($post);

#		The following assertions won't work because findOneByTitle() will get the _modified_ post
#		because it is still in Doctrine's identity map:
#
#		$post = $this->postRepository->findOneByTitle('Sample');
#		$this->assertNotNull($post);
#		$this->assertEquals('Sample', $post->getTitle());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modificationsOnRetrievedEntitiesArePersistedIfUpdateHasBeenCalled() {
		$post = new Post();
		$post->setTitle('Sample');
		$this->postRepository->add($post);

		$this->persistenceManager->persistAll();

		$post = $this->postRepository->findOneByTitle('Sample');
		$post->setTitle('Modified Sample');
		$this->postRepository->update($post);

		$this->persistenceManager->persistAll();

		$post = $this->postRepository->findOneByTitle('Modified Sample');
		$this->assertNotNull($post);
		$this->assertEquals('Modified Sample', $post->getTitle());
	}
}
?>