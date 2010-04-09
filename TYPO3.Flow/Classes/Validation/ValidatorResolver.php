<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation;

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
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $baseValidatorConjunctions = array();

	/**
	 * Constructs the validator resolver
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the compomenent manager
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Get a validator for a given data type. Returns a validator implementing
	 * the F3\FLOW3\Validation\Validator\ValidatorInterface or NULL if no validator
	 * could be resolved.
	 *
	 * @param string $validatorType Either one of the built-in data types or fully qualified validator class name
	 * @param array $validatorOptions Options to be passed to the validator
	 * @return F3\FLOW3\Validation\Validator\ValidatorResolver Validator Resolver or NULL if none found.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createValidator($validatorType, array $validatorOptions = array()) {
		$validatorObjectName = $this->resolveValidatorObjectName($validatorType);
		if ($validatorObjectName === FALSE) return NULL;
		$validator = $this->objectManager->get($validatorObjectName);
		if (!($validator instanceof \F3\FLOW3\Validation\Validator\ValidatorInterface)) {
			return NULL;
		}
		$validator->setOptions($validatorOptions);
		return $validator;
	}

	/**
	 * Resolves and returns the base validator conjunction for the given data type.
	 *
	 * If no validator could be resolved (which usually means that no validation is necessary),
	 * NULL is returned.
	 *
	 * @param string $targetClassName Fully qualified class name of the target class, ie. the class which should be validated
	 * @return F3\FLOW3\Validation\Validator\ConjunctionValidator The validator conjunction or NULL
	 * @author Robert Lemke <robert@typo3.org
	 */
	public function getBaseValidatorConjunction($targetClassName) {
		if (!isset($this->baseValidatorConjunctions[$targetClassName])) {
			$this->baseValidatorConjunctions[$targetClassName] = $this->buildBaseValidatorConjunction($targetClassName);
		}
		return $this->baseValidatorConjunctions[$targetClassName];
	}

	/**
	 * Detects and registers any validators for arguments:
	 * - by the data type specified in the @param annotations
	 * - additional validators specified in the @validate annotations of a method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return array An Array of ValidatorConjunctions for each method parameters.
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctions($className, $methodName) {
		$validatorConjunctions = array();

		$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		if (count($methodParameters) === 0) {
			return $validatorConjunctions;
		}

		foreach ($methodParameters as $parameterName => $methodParameter) {
			$validatorConjunction = $this->createValidator('F3\FLOW3\Validation\Validator\ConjunctionValidator');

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

		$methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
		if (isset($methodTagsValues['validate'])) {
			foreach ($methodTagsValues['validate'] as $validateValue) {
				$parsedAnnotation = $this->parseValidatorAnnotation($validateValue);
				foreach ($parsedAnnotation['validators'] as $validatorConfiguration) {
					$newValidator = $this->createValidator($validatorConfiguration['validatorName'], $validatorConfiguration['validatorOptions']);
					if ($newValidator === NULL) {
						throw new \F3\FLOW3\Validation\Exception\NoSuchValidatorException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Could not resolve class name for  validator "' . $validatorConfiguration['validatorName'] . '".', 1239853109);
					}
					if (isset($validatorConjunctions[$parsedAnnotation['argumentName']])) {
						$validatorConjunctions[$parsedAnnotation['argumentName']]->addValidator($newValidator);
					} elseif (strpos($parsedAnnotation['argumentName'], '.') !== FALSE) {
						$objectPath = explode('.', $parsedAnnotation['argumentName']);
						$argumentName = array_shift($objectPath);
						$validatorConjunctions[$argumentName]->addValidator($this->buildSubObjectValidator($objectPath, $newValidator));
					} else {
						throw new \F3\FLOW3\Validation\Exception\InvalidValidationConfigurationException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Validator specified for argument name "' . $parsedAnnotation['argumentName'] . '", but this argument does not exist.', 1253172726);
					}
				}
			}
		}
		return $validatorConjunctions;
	}

	/**
	 * Builds a chain of nested object validators by specification of the given
	 * object path.
	 *
	 * @param array $objectPath The object path
	 * @param \F3\FLOW3\Validation\Validator\ValidatorInterface $propertyValidator The validator which should be added to the property specified by objectPath
	 * @return \F3\FLOW3\Validation\Validator\GenericObjectValidator
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildSubObjectValidator(array $objectPath, \F3\FLOW3\Validation\Validator\ValidatorInterface $propertyValidator) {
		$rootObjectValidator = $this->createValidator('F3\FLOW3\Validation\Validator\GenericObjectValidator');
		$parentObjectValidator = $rootObjectValidator;

		while (count($objectPath) > 1) {
			$subObjectValidator = $this->createValidator('F3\FLOW3\Validation\Validator\GenericObjectValidator');
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
	 * a model) through some @validate annotations on properties.
	 *
	 * Additionally, if a custom validator was defined for the class in question, it will be added
	 * to the end of the conjunction. A custom validator is found if it follows the naming convention
	 * "Replace '\Model\' by '\Validator\' and append "Validator".
	 *
	 * Example: $dataType is F3\Foo\Domain\Model\Quux, then the Validator will be found if it has the
	 * name F3\Foo\Domain\Validator\QuuxValidator
	 *
	 * @param string $targetClassName The data type to build the validation conjunction for. Needs to be the fully qualified class name.
	 * @return F3\FLOW3\Validation\Validator\ConjunctionValidator The validator conjunction or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function buildBaseValidatorConjunction($targetClassName) {
		$conjunctionValidator = $this->objectManager->get('F3\FLOW3\Validation\Validator\ConjunctionValidator');

			// Model based validator
		if (class_exists($targetClassName)) {
			$validatorCount = 0;
			$objectValidator = $this->createValidator('F3\FLOW3\Validation\Validator\GenericObjectValidator');

			foreach ($this->reflectionService->getClassPropertyNames($targetClassName) as $classPropertyName) {
				$classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($targetClassName, $classPropertyName);

				$propertyTargetClassName = trim(implode('' , $classPropertyTagsValues['var']));
				if (class_exists($propertyTargetClassName)) {
					$subObjectValidator = $this->buildBaseValidatorConjunction($propertyTargetClassName);
					if ($subObjectValidator !== NULL) {
						$objectValidator->addPropertyValidator($classPropertyName, $subObjectValidator);
					}
				}

				if (!isset($classPropertyTagsValues['validate'])) continue;

				foreach ($classPropertyTagsValues['validate'] as $validateValue) {
					$parsedAnnotation = $this->parseValidatorAnnotation($validateValue);
					foreach ($parsedAnnotation['validators'] as $validatorConfiguration) {
						$newValidator = $this->createValidator($validatorConfiguration['validatorName'], $validatorConfiguration['validatorOptions']);
						if ($newValidator === NULL) {
							throw new \F3\FLOW3\Validation\Exception\NoSuchValidatorException('Invalid validate annotation in ' . $targetClassName . '::' . $classPropertyName . ': Could not resolve class name for  validator "' . $validatorConfiguration['validatorName'] . '".', 1241098027);
						}
						$objectValidator->addPropertyValidator($classPropertyName, $newValidator);
						$validatorCount ++;
					}
				}
			}
			if ($validatorCount > 0) $conjunctionValidator->addValidator($objectValidator);
		}

			// Custom validator for the class
		$possibleValidatorClassName = str_replace('\\Model\\', '\\Validator\\', $targetClassName) . 'Validator';
		$customValidator = $this->createValidator($possibleValidatorClassName);
		if ($customValidator !== NULL) {
			$conjunctionValidator->addValidator($customValidator);
		}

		return count($conjunctionValidator) > 0 ? $conjunctionValidator : NULL;
	}

	/**
	 * Parses the validator options given in @validate annotations.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseValidatorAnnotation($validateValue) {
		$matches = array();
		if ($validateValue[0] === '$') {
			$parts = explode(' ', $validateValue, 2);
			$validatorConfiguration = array('argumentName' => ltrim($parts[0], '$'), 'validators' => array());
			preg_match_all(self::PATTERN_MATCH_VALIDATORS, $parts[1], $matches, PREG_SET_ORDER);
		} else {
			$validatorConfiguration = array('validators' => array());
			preg_match_all(self::PATTERN_MATCH_VALIDATORS, $validateValue, $matches, PREG_SET_ORDER);
		}

		foreach ($matches as $match) {
			$validatorOptions = array();
			if (isset($match['validatorOptions'])) {
				$validatorOptions = $this->parseValidatorOptions($match['validatorOptions']);
			}
			$validatorConfiguration['validators'][] = array('validatorName' => $match['validatorName'], 'validatorOptions' => $validatorOptions);
		}

		return $validatorConfiguration;
	}

	/**
	 * Parses $rawValidatorOptions not containing quoted option values.
	 * $rawValidatorOptions will be an empty string afterwards (pass by ref!).
	 *
	 * @param string &$rawValidatorOptions
	 * @return array An array of optionName/optionValue pairs
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseValidatorOptions($rawValidatorOptions) {
		$validatorOptions = array();
		$parsedValidatorOptions = array();
		preg_match_all(self::PATTERN_MATCH_VALIDATOROPTIONS, $rawValidatorOptions, $validatorOptions, PREG_SET_ORDER);
		foreach ($validatorOptions as $validatorOption) {
			$parsedValidatorOptions[trim($validatorOption['optionName'])] = trim($validatorOption['optionValue']);
		}
		array_walk($parsedValidatorOptions, array($this, 'unquoteString'));
		return $parsedValidatorOptions;
	}

	/**
	 * Removes escapings from a given argument string and trims the outermost
	 * quotes.
	 * 
	 * This method is meant as a helper for regular expression results.
	 *
	 * @param string &$quotedValue Value to unquote
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function unquoteString(&$quotedValue) {
		switch ($quotedValue[0]) {
			case '"':
				$quotedValue = str_replace('\"', '"', trim($quotedValue, '"'));
			break;
			case '\'':
				$quotedValue = str_replace('\\\'', '\'', trim($quotedValue, '\''));
			break;
		}
		$quotedValue = str_replace('\\\\', '\\', $quotedValue);
	}

	/**
	 * Returns an object of an appropriate validator for the given type. If no
	 * validator is available NULL is returned
	 *
	 * @param string $validatorType Either the fully qualified class name of the validator or the short name of a built-in validator
	 * @return string Name of the validator object or FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveValidatorObjectName($validatorType) {
		$validatorType = ltrim($validatorType, '\\');

		if ($this->objectManager->isRegistered($validatorType)) {
			return $validatorType;
		}

		$possibleClassName = 'F3\FLOW3\Validation\Validator\\' . $this->getValidatorType($validatorType) . 'Validator';
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
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