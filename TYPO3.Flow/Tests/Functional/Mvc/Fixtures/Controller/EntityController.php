<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;

/**
 * A TestEntity controller fixture
 */
class EntityController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository
	 */
	protected $testEntityRepository;

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $entity
	 * @return string
	 */
	public function showAction(TestEntity $entity) {
		return $entity->getName();
	}

	/**
	 * @return void
	 */
	protected function initializeUpdateAction() {
		/** @var \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration $propertyMappingConfiguration */
		$propertyMappingConfiguration = $this->arguments['entity']->getPropertyMappingConfiguration();
		$propertyMappingConfiguration
			->allowAllProperties()
			->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
	}

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $entity
	 * @return string
	 */
	public function updateAction(TestEntity $entity) {
		$this->testEntityRepository->update($entity);
		return sprintf('Entity "%s" updated', $entity->getName());
	}


}
?>