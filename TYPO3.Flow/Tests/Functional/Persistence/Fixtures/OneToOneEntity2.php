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
 * @ORM\Table(name="persistence_onetooneentity2")
 */
class OneToOneEntity2 {

	/**
	 * Bidirectional relation inverse side
	 * @var OneToOneEntity
	 * @ORM\OneToOne(mappedBy="bidirectionalRelation")
	 */
	protected $bidirectionalRelation;

}