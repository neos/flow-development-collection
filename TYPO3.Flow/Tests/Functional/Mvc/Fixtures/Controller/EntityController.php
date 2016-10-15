<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Property\TypeConverter\DateTimeConverter;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository;

/**
 * A TestEntity controller fixture
 */
class EntityController extends ActionController
{
    /**
     * @Flow\Inject
     * @var TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @param TestEntity $entity
     * @return string
     */
    public function showAction(TestEntity $entity)
    {
        return $entity->getName();
    }

    /**
     * @return void
     */
    protected function initializeUpdateAction()
    {
        $propertyMappingConfiguration = $this->arguments->getArgument('entity')->getPropertyMappingConfiguration();
        $propertyMappingConfiguration
            ->allowAllProperties()
            ->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
        $propertyMappingConfiguration
            ->forProperty('subEntities.*')
            ->allowAllProperties()
            ->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
        $propertyMappingConfiguration
            ->forProperty('subEntities.*.date')
            ->setTypeConverterOption(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
    }

    /**
     * @param TestEntity $entity
     * @return string
     */
    public function updateAction(TestEntity $entity)
    {
        $this->testEntityRepository->update($entity);
        return sprintf('Entity "%s" updated', $entity->getName());
    }

    /**
     * @return string
     */
    protected function getFlattenedValidationErrorMessage()
    {
        $message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
        foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyPath => $errors) {
            foreach ($errors as $error) {
                $message .= 'Error for ' . $propertyPath . ':  ' . $error->render() . PHP_EOL;
            }
        }

        return $message;
    }
}
