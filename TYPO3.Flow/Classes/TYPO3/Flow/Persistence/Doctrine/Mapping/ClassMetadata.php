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
 */
class ClassMetadata extends \Doctrine\ORM\Mapping\ClassMetadata {

	/**
	 * Gets the ReflectionClass instance of the mapped class.
	 *
	 * @return \TYPO3\Flow\Reflection\ClassReflection
	 */
	public function getReflectionClass() {
		if ($this->reflClass === NULL) {
			$this->_initializeReflection();
		}
		return $this->reflClass;
	}

	/**
	 * Initializes $this->reflClass and a number of related variables.
	 *
	 * @param \Doctrine\Common\Persistence\Mapping\ReflectionService $reflService
	 * @return void
	 */
	public function initializeReflection($reflService) {
		$this->_initializeReflection();
	}

	/**
	 * Initializes $this->reflClass and a number of related variables.
	 *
	 * @return void
	 */
	protected function _initializeReflection() {
		$this->reflClass = new \TYPO3\Flow\Reflection\ClassReflection($this->name);
		$this->namespace = $this->reflClass->getNamespaceName();
		$this->name = $this->rootEntityName = $this->reflClass->getName();
		$this->table['name'] = $this->reflClass->getShortName();
	}
}

?>