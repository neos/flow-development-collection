<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * Class Schema Builder
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Persistence_ClassSchemaBuilder {

	/**
	 * The reflection service
	 *
	 * @var F3_FLOW3_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Reflection_Service $reflectionService The reflection service
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Builds a class schema from the specified class
	 *
	 * @param F3_FLOW3_Reflection_Class $class Reflection of the class to be analyzed
	 * @return F3_FLOW3_Persistence_ClassSchema
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_Persistence_Exception_InvalidClass if the specified class does not exist
	 */
	public function build($className) {
		if (!class_exists($className)) throw new F3_FLOW3_Persistence_Exception_InvalidClass('Unknown class "' . $className . '".', 1214495364);

		$classSchema = new F3_FLOW3_Persistence_ClassSchema($className);

		if ($this->reflectionService->isClassTaggedWith($className, 'entity')) {
			$classSchema->setModelType(F3_FLOW3_Persistence_ClassSchema::MODELTYPE_ENTITY);
		} elseif ($this->reflectionService->isClassTaggedWith($className, 'repository')) {
			$classSchema->setModelType(F3_FLOW3_Persistence_ClassSchema::MODELTYPE_REPOSITORY);
		} elseif ($this->reflectionService->isClassTaggedWith($className, 'valueobject')) {
			$classSchema->setModelType(F3_FLOW3_Persistence_ClassSchema::MODELTYPE_VALUEOBJECT);
		}

		foreach ($this->reflectionService->getClassPropertyNames($className) as $propertyName) {
			if (!$this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'transient') && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'var')) {
				$classSchema->setProperty($propertyName, implode(' ', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')));
			}
		}

		return $classSchema;
	}

}
?>