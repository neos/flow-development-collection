<?php
namespace TYPO3\FLOW3\Persistence\Doctrine\Mapping;

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

/**
 * A factory for Doctrine to create our ClassMetadata instances, aware of
 * the object manager.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory {

	/**
	 * Creates a new ClassMetadata instance for the given class name.
	 *
	 * @param string $className
	 * @return \TYPO3\FLOW3\Persistence\Doctrine\Mapping\ClassMetadata
	 */
	protected function newClassMetadataInstance($className) {
		$classMetadata = new \TYPO3\FLOW3\Persistence\Doctrine\Mapping\ClassMetadata($className);
		return $classMetadata;
	}

}

?>