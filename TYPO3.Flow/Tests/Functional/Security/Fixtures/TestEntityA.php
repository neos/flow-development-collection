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
class TestEntityA {

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityB
	 * @ORM\OneToOne(inversedBy="relatedEntityA")
	 */
	protected $relatedEntityB;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityB $relatedEntityB
	 */
	public function __construct($relatedEntityB) {
		$this->relatedEntityB = $relatedEntityB;
	}

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityB $relatedEntityB
	 */
	public function setRelatedEntityB($relatedEntityB) {
		$this->relatedEntityB = $relatedEntityB;
	}

	/**
	 * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityB
	 */
	public function getRelatedEntityB() {
		return $this->relatedEntityB;
	}

}
