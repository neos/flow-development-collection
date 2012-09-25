<?php
namespace TYPO3\Flow\Persistence\Doctrine\Mapping;

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
 * A factory for Doctrine to create our ClassMetadata instances, aware of
 * the object manager.
 *
 */
class ClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory {

	/**
	 * Creates a new ClassMetadata instance for the given class name.
	 *
	 * @param string $className
	 * @return \TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadata
	 */
	protected function newClassMetadataInstance($className) {
		return new \TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadata($className);
	}

}

?>