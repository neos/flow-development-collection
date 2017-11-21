<?php
namespace Neos\Flow\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\ObjectValidationFailedException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;
use Neos\Flow\Validation\ValidatorResolver;

/**
 * Flow's Doctrine PersistenceManager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ObjectValidationListener
{
    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * An onFlush event listener used to validate entities upon persistence.
     *
     * @param OnFlushEventArgs $eventArgs
     * @return void
     * @throws ObjectValidationFailedException
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $entityInsertions = $unitOfWork->getScheduledEntityInsertions();

        $validatedInstancesContainer = new \SplObjectStorage();
        $knownValueObjects = [];
        foreach ($entityInsertions as $entity) {
            $className = TypeHandling::getTypeForValue($entity);
            if ($this->reflectionService->getClassSchema($className)->getModelType() === ClassSchema::MODELTYPE_VALUEOBJECT) {
                $identifier = $this->persistenceManager->getIdentifierByObject($entity);

                if (isset($knownValueObjects[$className][$identifier]) || $unitOfWork->getEntityPersister($className)->exists($entity)) {
                    unset($entityInsertions[spl_object_hash($entity)]);
                    continue;
                }

                $knownValueObjects[$className][$identifier] = true;
            }
            $this->validateObject($entity, $validatedInstancesContainer);
        }

        ObjectAccess::setProperty($unitOfWork, 'entityInsertions', $entityInsertions, true);

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->validateObject($entity, $validatedInstancesContainer);
        }
    }

    /**
     * Validates the given object and throws an exception if validation fails.
     *
     * @param object $object
     * @param \SplObjectStorage $validatedInstancesContainer
     * @return void
     * @throws ObjectValidationFailedException
     */
    protected function validateObject($object, \SplObjectStorage $validatedInstancesContainer)
    {
        $className = $this->entityManager->getClassMetadata(get_class($object))->getName();
        $validator = $this->validatorResolver->getBaseValidatorConjunction($className, ['Persistence', 'Default']);
        if ($validator === null) {
            return;
        }

        $validator->setValidatedInstancesContainer($validatedInstancesContainer);
        $validationResult = $validator->validate($object);
        if ($validationResult->hasErrors()) {
            $errorMessages = '';
            $errorCount = 0;
            $allErrors = $validationResult->getFlattenedErrors();
            foreach ($allErrors as $path => $errors) {
                $errorMessages .= $path . ':' . PHP_EOL;
                foreach ($errors as $error) {
                    $errorCount++;
                    $errorMessages .= (string)$error . PHP_EOL;
                }
            }
            throw new ObjectValidationFailedException('An instance of "' . get_class($object) . '" failed to pass validation with ' . $errorCount . ' error(s): ' . PHP_EOL . $errorMessages, 1322585164);
        }
    }
}
