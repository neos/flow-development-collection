<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An entity for tests
 *
 * @Flow\Entity
 */
class TestEntityD {

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
	 * @ORM\OneToMany(mappedBy="relatedEntityD")
	 */
	protected $relatedEntityC;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
	 * @ORM\ManyToOne(inversedBy="oneToManyToRelatedEntityD")
	 */
	protected $manyToOneToRelatedEntityC;

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC $oneToManyToRelatedEntityC
	 */
	public function setOneToManyToRelatedEntityC($oneToManyToRelatedEntityC) {
		$this->oneToManyToRelatedEntityC = $oneToManyToRelatedEntityC;
	}

	/**
	 * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
	 */
	public function getOneToManyToRelatedEntityC() {
		return $this->oneToManyToRelatedEntityC;
	}

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC $relatedEntityC
	 */
	public function setRelatedEntityC($relatedEntityC) {
		$this->relatedEntityC = $relatedEntityC;
	}

	/**
	 * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityC
	 */
	public function getRelatedEntityC() {
		return $this->relatedEntityC;
	}
}
