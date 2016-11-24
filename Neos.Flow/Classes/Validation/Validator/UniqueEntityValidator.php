<?php
namespace Neos\Flow\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;
use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for uniqueness of entities.
 *
 * @api
 */
class UniqueEntityValidator extends AbstractValidator
{
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
     * @var array
     */
    protected $supportedOptions = [
        'identityProperties' => [null, 'List of custom identity properties.', 'array']
    ];

    /**
     * Checks if the given value is a unique entity depending on it's identity properties or
     * custom configured identity properties.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @throws InvalidValidationOptionsException
     * @api
     */
    protected function isValid($value)
    {
        if (!is_object($value)) {
            throw new InvalidValidationOptionsException('The value supplied for the UniqueEntityValidator must be an object.', 1358454270);
        }

        $classSchema = $this->reflectionService->getClassSchema(TypeHandling::getTypeForValue($value));
        if ($classSchema === null || $classSchema->getModelType() !== ClassSchema::MODELTYPE_ENTITY) {
            throw new InvalidValidationOptionsException('The object supplied for the UniqueEntityValidator must be an entity.', 1358454284);
        }

        if ($this->options['identityProperties'] !== null) {
            $identityProperties = $this->options['identityProperties'];
            foreach ($identityProperties as $propertyName) {
                if ($classSchema->hasProperty($propertyName) === false) {
                    throw new InvalidValidationOptionsException(sprintf('The custom identity property name "%s" supplied for the UniqueEntityValidator does not exists in "%s".', $propertyName, $classSchema->getClassName()), 1358960500);
                }
            }
        } else {
            $identityProperties = array_keys($classSchema->getIdentityProperties());
        }

        if (count($identityProperties) === 0) {
            throw new InvalidValidationOptionsException('The object supplied for the UniqueEntityValidator must have at least one identity property.', 1358459831);
        }

        $identifierProperties = $this->reflectionService->getPropertyNamesByAnnotation($classSchema->getClassName(), 'Doctrine\ORM\Mapping\Id');
        if (count($identifierProperties) > 1) {
            throw new InvalidValidationOptionsException('The object supplied for the UniqueEntityValidator must only have one identifier property @ORM\Id.', 1358501745);
        }

        $identifierPropertyName = count($identifierProperties) > 0 ? array_shift($identifierProperties) : 'Persistence_Object_Identifier';

        $query = $this->persistenceManager->createQueryForType($classSchema->getClassName());
        $constraints = [$query->logicalNot($query->equals($identifierPropertyName, $this->persistenceManager->getIdentifierByObject($value)))];
        foreach ($identityProperties as  $propertyName) {
            $constraints[] = $query->equals($propertyName, ObjectAccess::getProperty($value, $propertyName));
        }

        if ($query->matching($query->logicalAnd($constraints))->count() > 0) {
            $this->addError('Another entity with the same unique identifiers already exists', 1355785874);
        }
    }
}
