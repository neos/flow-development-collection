<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence;

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
 * Class Schemata Builder
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ClassSchemataBuilder {

	/**
	 * The reflection service
	 *
	 * @var F3::FLOW3::Reflection::Service
	 */
	protected $reflectionService;

	/**
	 * Constructor
	 *
	 * @param F3::FLOW3::Reflection::Service $reflectionService The reflection service
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3::FLOW3::Reflection::Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Builds class schemata from the specified classes
	 *
	 * @param array $classNames Names of the classes to build schemata from
	 * @return array of F3::FLOW3::Persistence::ClassSchema
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Persistence::Exception::InvalidClass if one of the specified classes does not exist
	 */
	public function build(array $classNames) {
		$classSchemata = array();
		foreach ($classNames as $className) {
			if (!class_exists($className)) throw new F3::FLOW3::Persistence::Exception::InvalidClass('Unknown class "' . $className . '".', 1214495364);

			$modelType = NULL;
			if ($this->reflectionService->isClassTaggedWith($className, 'entity')) {
				$modelType = F3::FLOW3::Persistence::ClassSchema::MODELTYPE_ENTITY;
			} elseif ($this->reflectionService->isClassImplementationOf($className, 'F3::FLOW3::Persistence::RepositoryInterface')) {
				$modelType = F3::FLOW3::Persistence::ClassSchema::MODELTYPE_REPOSITORY;
			} elseif ($this->reflectionService->isClassTaggedWith($className, 'valueobject')) {
				$modelType = F3::FLOW3::Persistence::ClassSchema::MODELTYPE_VALUEOBJECT;
			} else {
				continue;
			}

			$classSchema = new F3::FLOW3::Persistence::ClassSchema($className);
			$classSchema->setModelType($modelType);
			foreach ($this->reflectionService->getClassPropertyNames($className) as $propertyName) {
				if ($this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'identifier')) {
					$classSchema->setIdentifierProperty($propertyName);
				}
				if (!$this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'transient') && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'var')) {
					$classSchema->setProperty($propertyName, implode(' ', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')));
				}
			}
			$classSchemata[$className] = $classSchema;
		}
		return $classSchemata;
	}

}
?>