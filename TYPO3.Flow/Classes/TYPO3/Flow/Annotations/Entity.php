<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Marks an object as an entity.
 *
 * Behaves like \Doctrine\ORM\Mapping\Entity so it is interchangeable
 * with that.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Entity {

	/**
	 * Name of the repository class to use for managing the entity.
	 * @var string
	 */
	public $repositoryClass;

	/**
	 * Whether the entity should be read-only.
	 * @var boolean
	 */
	public $readOnly = FALSE;

}

?>