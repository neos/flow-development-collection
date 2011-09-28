<?php
namespace TYPO3\FLOW3\Validation;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Object\Configuration\Configuration;
use \TYPO3\FLOW3\Validation\Validator\ValidatorInterface;
use TYPO3\FLOW3\Validation\Validator\GenericObjectValidator;
use TYPO3\FLOW3\Validation\Validator\ConjunctionValidator;

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @FLOW3\Scope("singleton")
 */
class ValidatorResolver {

	/**
	 * Match validator names and options
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATORS = '/
			(?:^|,\s*)
			(?P<validatorName>[a-z0-9\\\\]+)
			\s*
			(?:\(
				(?P<validatorOptions>(?:\s*[a-z0-9]+\s*=\s*(?:
					"(?:\\\\"|[^"])*"
					|\'(?:\\\\\'|[^\'])*\'
					|(?:\s|[^,"\']*)
				)(?:\s|,)*)*)
			\))?
		/ixS';

	/**
	 * Match validator options (to parse actual options)
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATOROPTIONS = '/
			\s*
			(?P<optionName>[a-z0-9]+)
			\s*=\s*
			(?P<optionValue>
				"(?:\\\\"|[^"])*"
				|\'(?:\\\\\'|[^\'])*\'
				|(?:\s|[^,"\']*)
			)
		/ixS';

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $baseValidatorConjunctions = array();

	/**
	 * Get a validator for a given data type. Returns a validator implementing
	 * the TYPO3\FLOW3\Validation\Validator\ValidatorInterface or NULL if no validator
	 * could be resolved.
	 *
	 * @param string $validatorType Either one of the built-in data types or fully qualified validator class name
	 * @param array $validatorOptions Options to be passed to the validator
	 * @return \TYPO3\FLOW3\Validation\Validator\ValidatorResolver Validator Resolver or NULL if none found.
	 */
	public function createValidator($validatorType, array $validatorOptions = array()) {
		$validatorObjectName = $this->resolveValidatorObjectName($validatorType);
		if ($validatorObjectName === FALSE) return NULL;

		switch ($this->objectManager->getScope($validatorObjectName)) {
			case Configuration::SCOPE_PROTOTYPE:
				$validator = new $validatorObjectName($validatorOptions);
				break;
			case Configuration::SCOPE_SINGLETON:
				$validator = $this->objectManager->get($validatorObjectName);
				break;
			default:
				throw new \TYPO3\FLOW3\Validation\Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" is not of scope singleton or prototype!', 1300694835);
		}

		if (!($validator instanceof ValidatorInterface)) {
			throw new \TYPO3\FLOW3\Validation\Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" does not implement TYPO3\FLOW3\Validation\Validator\ValidatorInterface!', 1300694875);
		}

		return $validator;
	}

	/**
	 * Resolves and returns the base validator conjunction for the given data type.
	 *
	 * If no validation is necessary, the returned validator is empty.
	 *
	 * @param string $targetClassName Fully qualified class name of the target class, ie. the class which should be validated
	 * @return \TYPO3\FLOW3\Validation\Validator\ConjunctionValidator The validator conjunction
	 */
	public function getBaseValidatorConjunction($targetClassName) {
		$targetClassName = trim($targetClassName, ' \\');
		if (!array_key_exists($targetClassName, $this->baseValidatorConjunctions)) {
			$this->buildBaseValidatorConjunction($targetClassName);
		}
		return $this->baseValidatorConjunctions[$targetClassName];
	}

	/**
	 * Detects and registers any validators for arguments:
	 * - by the data type specified in the param annotations
	 * - additional validators specified in the validate annotations of a method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return array An Array of ValidatorConjunctions for each method parameters.
	 */
	public function buildMethodArgumentsValidatorConjunctions($className, $methodName) {
		$validatorConjunctions = array();

		$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		if (count($methodParameters) === 0) {
			return $validatorConjunctions;
		}

		foreach ($methodParameters as $parameterName => $methodParameter) {
			$validatorConjunction = $this->createValidator('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator');

			if (!array_key_exists('type' , $methodParameter)) {
				throw new \TYPO3\FLOW3\Validation\Exception\InvalidTypeHintException('Missing type information, probably no @param annotation for parameter "$' . $parameterName . '" in ' . $className . '->' . $methodName . '()', 1281962564);
			}
			if (strpos($methodParameter['type'], '\\') === FALSE) {
				$typeValidator = $this->createValidator($methodParameter['type']);
			} elseif (strpos($methodParameter['type'], '\\Model\\') !== FALSE) {
				$possibleValidatorClassName = str_replace('\\Model\\', '\\Validator\\', $methodParameter['type']) . 'Validator';
				$typeValidator = $this->createValidator($possibleValidatorClassName);
			} else {
				$typeValidator = NULL;
			}

			if ($typeValidator !== NULL) {
				$validatorConjunction->addValidator($typeValidator);
			}
			$validatorConjunctions[$parameterName] = $validatorConjunction;
		}

		$validateAnnotations = $this->reflectionService->getMethodAnnotations($className, $methodName, 'TYPO3\FLOW3\Annotations\Validate');
		foreach ($validateAnnotations as $validateAnnotation) {
			$newValidator = $this->createValidator($validateAnnotation->type, $validateAnnotation->options);
			if ($newValidator === NULL) {
				throw new \TYPO3\FLOW3\Validation\Exception\NoSuchValidatorException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Could not resolve class name for  validator "' . $validateAnnotation->type . '".', 1239853109);
			}
			if (isset($validatorConjunctions[$validateAnnotation->argumentName])) {
				$validatorConjunctions[$validateAnnotation->argumentName]->addValidator($newValidator);
			} elseif (strpos($validateAnnotation->argumentName, '.') !== FALSE) {
				$objectPath = explode('.', $validateAnnotation->argumentName);
				$argumentName = array_shift($objectPath);
				$validatorConjunctions[$argumentName]->addValidator($this->buildSubObjectValidator($objectPath, $newValidator));
			} else {
				throw new \TYPO3\FLOW3\Validation\Exception\InvalidValidationConfigurationException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Validator specified for argument name "' . $validateAnnotation->argumentName . '", but this argument does not exist.', 1253172726);
			}
		}
		return $validatorConjunctions;
	}

	/**
	 * Builds a chain of nested object validators by specification of the given
	 * object path.
	 *
	 * @param array $objectPath The object path
	 * @param \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $propertyValidator The validator which should be added to the property specified by objectPath
	 * @return \TYPO3\FLOW3\Validation\Validator\GenericObjectValidator
	 */
	protected function buildSubObjectValidator(array $objectPath, \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $propertyValidator) {
		$rootObjectValidator = new GenericObjectValidator(array());
		$parentObjectValidator = $rootObjectValidator;

		while (count($objectPath) > 1) {
			$subObjectValidator = new GenericObjectValidator(array());
			$subPropertyName = array_shift($objectPath);
			$parentObjectValidator->addPropertyValidator($subPropertyName, $subObjectValidator);
			$parentObjectValidator = $subObjectValidator;
		}

		$parentObjectValidator->addPropertyValidator(array_shift($objectPath), $propertyValidator);
		return $rootObjectValidator;
	}

	/**
	 * Builds a base validator conjunction for the given data type.
	 *
	 * The base validation rules are those which were declared directly in a class (typically
	 * a model) through some validate annotations on properties.
	 *
	 * If a property holds a class for which a base validator exists, that property will be
	 * checked as well, regardless of a validate annotation
	 *
	 * Additionally, if a custom validator was defined for the class in question, it will be added
	 * to the end of the conjunction. A custom validator is found if it follows the naming convention
	 * "Replace '\Model\' by '\Validator\' and append 'Validator'".
	 *
	 * Example: $targetClassName is TYPO3\Foo\Domain\Model\Quux, then the validator will be found if it has the
	 * name TYPO3\Foo\Domain\Validator\QuuxValidator
	 *
	 * @param string $targetClassName The data type to build the validation conjunction for. Needs to be the fully qualified class name.
	 * @return \TYPO3\FLOW3\Validation\Validator\ConjunctionValidator The validator conjunction
	 */
	protected function buildBaseValidatorConjunction($targetClassName) {
		$conjunctionValidator = new ConjunctionValidator();
		$this->baseValidatorConjunctions[$targetClassName] = $conjunctionValidator;

		if (class_exists($targetClassName)) {
				// Model based validator
			$objectValidator = new GenericObjectValidator(array());
			foreach ($this->reflectionService->getClassPropertyNames($targetClassName) as $classPropertyName) {
				$classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($targetClassName, $classPropertyName);

				try {
					$parsedType = \TYPO3\FLOW3\Utility\TypeHandling::parseType(trim(implode('' , $classPropertyTagsValues['var']), ' \\'));
				} catch (\TYPO3\FLOW3\Utility\Exception\InvalidTypeException $exception) {
					throw new \InvalidArgumentException(sprintf(' @var annotation of ' . $exception->getMessage(), 'class "' . $targetClassName . '", property "' . $classPropertyName . '"'), 1315564744);
				}
				$propertyTargetClassName = $parsedType['type'];
				if (\TYPO3\FLOW3\Utility\TypeHandling::isCollectionType($propertyTargetClassName) === TRUE) {
						$collectionValidator = $this->createValidator('TYPO3\FLOW3\Validation\Validator\CollectionValidator', array('elementType' =>$parsedType['elementType']));
						$objectValidator->addPropertyValidator($classPropertyName, $collectionValidator);
				} elseif (class_exists($propertyTargetClassName) && $this->objectManager->isRegistered($propertyTargetClassName) && $this->objectManager->getScope($propertyTargetClassName) === \TYPO3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
					$validatorForProperty = $this->getBaseValidatorConjunction($propertyTargetClassName);
					if (count($validatorForProperty) > 0) {
						$objectValidator->addPropertyValidator($classPropertyName, $validatorForProperty);
					}
				}

				$validateAnnotations = $this->reflectionService->getPropertyAnnotations($targetClassName, $classPropertyName, 'TYPO3\FLOW3\Annotations\Validate');
				foreach ($validateAnnotations as $validateAnnotation) {
					$newValidator = $this->createValidator($validateAnnotation->type, $validateAnnotation->options);
					if ($newValidator === NULL) {
						throw new \TYPO3\FLOW3\Validation\Exception\NoSuchValidatorException('Invalid validate annotation in ' . $targetClassName . '::' . $classPropertyName . ': Could not resolve class name for  validator "' . $validateAnnotation->type . '".', 1241098027);
					}
					$objectValidator->addPropertyValidator($classPropertyName, $newValidator);
				}
			}
			if (count($objectValidator->getPropertyValidators()) > 0) $conjunctionValidator->addValidator($objectValidator);

				// Custom validator for the class
			$possibleValidatorClassName = str_replace('\\Model\\', '\\Validator\\', $targetClassName) . 'Validator';
			$customValidator = $this->createValidator($possibleValidatorClassName);
			if ($customValidator !== NULL) {
				$conjunctionValidator->addValidator($customValidator);
			}
		}
	}

	/**
	 * Returns an object of an appropriate validator for the given type. If no
	 * validator is available NULL is returned
	 *
	 * @param string $validatorType Either the fully qualified class name of the validator or the short name of a built-in validator
	 * @return string Name of the validator object or FALSE
	 */
	protected function resolveValidatorObjectName($validatorType) {
		$validatorType = ltrim($validatorType, '\\');

		if ($this->objectManager->isRegistered($validatorType)) {
			return $validatorType;
		}

		$possibleClassName = 'TYPO3\FLOW3\Validation\Validator\\' . $this->getValidatorType($validatorType) . 'Validator';
		if ($this->objectManager->isRegistered($possibleClassName)) {
			return $possibleClassName;
		}

		return FALSE;
	}

	/**
	 * Used to map PHP types to validator types.
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 */
	protected function getValidatorType($type) {
		switch ($type) {
			case 'int':
				$type = 'Integer';
				break;
			case 'bool':
				$type = 'Boolean';
				break;
			case 'double':
				$type = 'Float';
				break;
			case 'numeric':
				$type = 'Number';
				break;
			case 'mixed':
				$type = 'Raw';
				break;
			default:
				$type = ucfirst($type);
		}
		return $type;
	}
}

?>