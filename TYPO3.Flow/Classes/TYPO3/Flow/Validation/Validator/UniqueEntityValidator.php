<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow,
	TYPO3\Flow\Reflection\ObjectAccess,
	TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for uniqueness of entities.
 *
 * @api
 */
class UniqueEntityValidator extends AbstractValidator {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'identityProperties' => array(NULL, 'List of custom identity properties.', 'array')
	);

	/**
	 * Checks if the given value is a unique entity depending on it's identity properties or
	 * custom configured identity properties.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
	 * @api
	 */
	protected function isValid($value) {
		if (!is_object($value)) {
			throw new InvalidValidationOptionsException('The value supplied for the UniqueEntityValidator must be an object.', 1358454270);
		}

		$classSchema = $this->reflectionService->getClassSchema($value);
		if ($classSchema === NULL || $classSchema->getModelType() !== \TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			throw new InvalidValidationOptionsException('The object supplied for the UniqueEntityValidator must be an entity.', 1358454284);
		}

		if ($this->options['identityProperties'] !== NULL) {
			$identityProperties = $this->options['identityProperties'];
			foreach ($identityProperties as $propertyName) {
				if ($classSchema->hasProperty($propertyName) === FALSE) {
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
		$constraints = array($query->logicalNot($query->equals($identifierPropertyName, $this->persistenceManager->getIdentifierByObject($value))));
		foreach ($identityProperties as  $propertyName) {
			$constraints[] = $query->equals($propertyName, ObjectAccess::getProperty($value, $propertyName));
		}

		if ($query->matching($query->logicalAnd($constraints))->count() > 0) {
			$this->addError('Another entity with the same unique identifiers already exists', 1355785874);
		}
	}

}
