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
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;

/**
 * A TestEntity controller fixture
 */
class EntityController extends ActionController
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $entity
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
        $this->arguments->getArgument('entity')->getPropertyMappingConfiguration()
            ->allowAllProperties()
            ->setTypeConverterOption(\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
        $this->arguments->getArgument('entity')->getPropertyMappingConfiguration()
            ->forProperty('subEntities.*')
            ->allowAllProperties()
            ->setTypeConverterOption(\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
    }

    /**
     * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $entity
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

    /**
     * @return void
     */
    protected function initializeValidateAction()
    {
        $this->arguments->getArgument('entity')->getPropertyMappingConfiguration()
            ->allowAllProperties()
            ->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
        $this->arguments->getArgument('entity')->getPropertyMappingConfiguration()
            ->forProperty('subEntities.*')
            ->allowAllProperties()
            ->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
    }

    /**
     * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $entity
     * @return string
     */
    public function validateAction(TestEntity $entity)
    {
        /* @var \TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator $spyValidator */
        $spyValidator = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');
        return sprintf('SpyValidator was executed %d times.', $spyValidator->executionCount());
    }

    /**
     * @return void
     */
    protected function initializeValidatePersistenceAction()
    {
        $this->arguments->getArgument('entity')->getPropertyMappingConfiguration()
            ->allowAllProperties()
            ->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
        $this->arguments->getArgument('entity')->getPropertyMappingConfiguration()
            ->forProperty('subEntities.*')
            ->allowAllProperties()
            ->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
    }

    /**
     * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $entity
     * @return string
     */
    public function validatePersistenceAction(TestEntity $entity)
    {
        $entity->setValidatedProperty('Some value set inside Action');
        $this->testEntityRepository->update($entity);
        $this->persistenceManager->persistAll();

        /* @var \TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator $spyValidator */
        $spyValidator = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');
        return sprintf('SpyValidator was executed %d times.', $spyValidator->executionCount());
    }
}
