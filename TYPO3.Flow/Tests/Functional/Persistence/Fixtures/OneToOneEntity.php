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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A simple entity for persistence tests of OneToOne relations.
 *
 * @Flow\Entity
 * @ORM\Table(name="persistence_onetooneentity")
 */
class OneToOneEntity {

	/**
	 * Self-referencing
	 * @var OneToOneEntity
	 * @ORM\OneToOne
	 */
	protected $selfReferencing;

	/**
	 * Bidirectional relation owning side
	 * @var OneToOneEntity2
	 * @ORM\OneToOne(inversedBy="bidirectionalRelation")
	 */
	protected $bidirectionalRelation;

	/**
	 * Unidirectional relation
	 * @var OneToOneEntity2
	 * @ORM\OneToOne
	 */
	protected $unidirectionalRelation;

}