<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * The Class Schemata Builder is used by the Persistence Manager to build class
 * schemata for all classes tagged as ValueObject or Entity.
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassSchemataBuilder {

	/**
	 * The reflection service
	 *
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * Constructor
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService The reflection service
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function __construct(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Builds class schemata from the specified classes
	 *
	 * @param array $classNames Names of the classes to build schemata from
	 * @return array of \F3\FLOW3\Persistence\ClassSchema
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidClass if one of the specified classes does not exist
	 * @internal
	 */
	public function build(array $classNames) {
		$classSchemata = array();
		foreach ($classNames as $className) {
			if (!$this->reflectionService->isClassReflected($className)) throw new \F3\FLOW3\Persistence\Exception\InvalidClass('Unknown class "' . $className . '".', 1214495364);

			$modelType = NULL;
			$aggregateRoot = FALSE;
			if ($this->reflectionService->isClassTaggedWith($className, 'entity')) {
				$modelType = \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY;
				if ($this->reflectionService->isClassReflected($className . 'Repository')) {
					$aggregateRoot = TRUE;
				}
			} elseif ($this->reflectionService->isClassTaggedWith($className, 'valueobject')) {
				$modelType = \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_VALUEOBJECT;
			} else {
				continue;
			}

			$classSchema = new \F3\FLOW3\Persistence\ClassSchema($className);
			$classSchema->setModelType($modelType);
			$classSchema->setAggregateRoot($aggregateRoot);
			foreach ($this->reflectionService->getClassPropertyNames($className) as $propertyName) {
				if (!$this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'transient') && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'var')) {
					$classSchema->addProperty($propertyName, implode(' ', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')), $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'lazy'));
				}
				if ($this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'uuid')) {
					$classSchema->setUUIDPropertyName($propertyName);
				}
				if ($this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'identity')) {
					$classSchema->markAsIdentityProperty($propertyName);
				}
			}
			$classSchemata[$className] = $classSchema;
		}
		return $classSchemata;
	}

}
?>