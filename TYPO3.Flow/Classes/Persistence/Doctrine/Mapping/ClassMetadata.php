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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ClassMetadata extends \Doctrine\ORM\Mapping\ClassMetadata {

	/**
	 * Initializes a new ClassMetadata instance that will hold the object-relational mapping
	 * metadata of the class with the given name.
	 *
	 * @param string $entityName The name of the entity class the new instance is used for.
	 */
	public function __construct($entityName) {
		parent::__construct($entityName);
		$this->reflClass = new \TYPO3\FLOW3\Reflection\ClassReflection($entityName);
		$this->namespace = $this->reflClass->getNamespaceName();
		$this->table['name'] = $this->reflClass->getShortName();
	}

}

?>