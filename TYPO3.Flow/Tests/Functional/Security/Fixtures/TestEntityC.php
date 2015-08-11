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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An entity for tests
 *
 * @Flow\Entity
 */
class TestEntityC {

	/**
	 * @var string
	 */
	protected $simpleStringProperty;

	/**
	 * @var array<string>
	 */
	protected $simpleArrayProperty;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD
	 * @ORM\OneToOne(inversedBy="relatedEntityC")
	 */
	protected $relatedEntityD;

	/**
	 * @var Collection<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD>
	 * @ORM\OneToMany(mappedBy="manyToOneToRelatedEntityC")
	 */
	protected $oneToManyToRelatedEntityD;

	/**
	 * @var Collection<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD>
	 * @ORM\ManyToMany
	 */
	protected $manyToManyToRelatedEntityD;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->manyToOneToRelatedEntityD = new ArrayCollection();
		$this->manyToManyToRelatedEntityD = new ArrayCollection();
	}

	/**
	 * @param Collection<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD> $manyToManyToRelatedEntityD
	 */
	public function setManyToManyToRelatedEntityD($manyToManyToRelatedEntityD) {
		$this->manyToManyToRelatedEntityD = $manyToManyToRelatedEntityD;
	}

	/**
	 * @return Collection<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD>
	 */
	public function getManyToManyToRelatedEntityD() {
		return $this->manyToManyToRelatedEntityD;
	}

	/**
	 * @param Collection<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD> $manyToOneToRelatedEntityD
	 */
	public function setManyToOneToRelatedEntityD($manyToOneToRelatedEntityD) {
		$this->manyToOneToRelatedEntityD = $manyToOneToRelatedEntityD;
	}

	/**
	 * @return Collection<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD>
	 */
	public function getManyToOneToRelatedEntityD() {
		return $this->manyToOneToRelatedEntityD;
	}

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD $relatedEntityD
	 */
	public function setRelatedEntityD($relatedEntityD) {
		$this->relatedEntityD = $relatedEntityD;
	}

	/**
	 * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD
	 */
	public function getRelatedEntityD() {
		return $this->relatedEntityD;
	}

	/**
	 * @param array<string> $simpleArrayProperty
	 */
	public function setSimpleArrayProperty($simpleArrayProperty) {
		$this->simpleArrayProperty = $simpleArrayProperty;
	}

	/**
	 * @return array<string>
	 */
	public function getSimpleArrayProperty() {
		return $this->simpleArrayProperty;
	}

	/**
	 * @param string $simpleStringProperty
	 */
	public function setSimpleStringProperty($simpleStringProperty) {
		$this->simpleStringProperty = $simpleStringProperty;
	}

	/**
	 * @return string
	 */
	public function getSimpleStringProperty() {
		return $this->simpleStringProperty;
	}
}
