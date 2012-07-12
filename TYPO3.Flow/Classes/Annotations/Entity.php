<?php
namespace TYPO3\FLOW3\Annotations;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Behaves like \Doctrine\ORM\Mapping\Entity so it is interchangeable
 * with that.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Entity {

	/**
	 * @var string
	 */
	public $repositoryClass;

	/**
	 * @var boolean
	 */
	public $readOnly = FALSE;

}

?>