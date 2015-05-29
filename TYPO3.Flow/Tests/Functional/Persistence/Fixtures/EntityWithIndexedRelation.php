<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

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
 * A sample entity that has a property with an indexed relation
 *
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class EntityWithIndexedRelation {

	/**
	 * @var Collection<AnnotatedIdentitiesEntity>
	 * @ORM\ManyToMany(indexBy="author")
	 */
	protected $annotatedIdentitiesEntities;

	/**
	 * @var Collection<RelatedIndexEntity>
	 * @ORM\OneToMany(indexBy="sorting", mappedBy="entityWithIndexedRelation")
	 */
	protected $relatedIndexEntities;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->annotatedIdentitiesEntities = new ArrayCollection();
		$this->relatedIndexEntities = new ArrayCollection();
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $annotatedIdentitiesEntities
	 */
	public function setAnnotatedIdentitiesEntities($annotatedIdentitiesEntities) {
		$this->annotatedIdentitiesEntities = $annotatedIdentitiesEntities;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getAnnotatedIdentitiesEntities() {
		return $this->annotatedIdentitiesEntities;
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $relatedIndexEntities
	 */
	public function setRelatedIndexEntities($relatedIndexEntities) {
		$this->relatedIndexEntities = $relatedIndexEntities;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getRelatedIndexEntities() {
		return $this->relatedIndexEntities;
	}

	/**
	 * @param string $sorting
	 * @param RelatedIndexEntity $relatedIndexEntity
	 */
	public function setRelatedIndexEntity($sorting, RelatedIndexEntity $relatedIndexEntity) {
		$relatedIndexEntity->setSorting($sorting);
		$relatedIndexEntity->setEntityWithIndexedRelation($this);
		$this->relatedIndexEntities->set($sorting, $relatedIndexEntity);
	}

}
