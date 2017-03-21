<?php
namespace Neos\Flow\Validation;

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
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;
use Neos\Flow\Validation\Validator\AggregateBoundaryValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Flow\Validation\Validator\GenericObjectValidator;
use Neos\Flow\Validation\Validator\ConjunctionValidator;

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ValidatorResolver
{
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
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var array
     */
    protected $baseValidatorConjunctions = [];

    /**
     * Get a validator for a given data type. Returns a validator implementing
     * the ValidatorInterface or NULL if no validator
     * could be resolved.
     *
     * @param string $validatorType Either one of the built-in data types or fully qualified validator class name
     * @param array $validatorOptions Options to be passed to the validator
     * @return ValidatorInterface
     * @throws Exception\NoSuchValidatorException
     * @throws Exception\InvalidValidationConfigurationException
     * @api
     */
    public function createValidator($validatorType, array $validatorOptions = [])
    {
        $validatorObjectName = $this->resolveValidatorObjectName($validatorType);
        if ($validatorObjectName === false) {
            return null;
        }

        switch ($this->objectManager->getScope($validatorObjectName)) {
            case Configuration::SCOPE_PROTOTYPE:
                $validator = new $validatorObjectName($validatorOptions);
                break;
            case Configuration::SCOPE_SINGLETON:
                if (count($validatorOptions) > 0) {
                    throw new Exception\InvalidValidationConfigurationException('The validator "' . $validatorObjectName . '" is of scope singleton, but configured to be used with options. A validator with options must be of scope prototype.', 1358958575);
                }
                $validator = $this->objectManager->get($validatorObjectName);
                break;
            default:
                throw new Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" is not of scope singleton or prototype!', 1300694835);
        }

        if (!($validator instanceof ValidatorInterface)) {
            throw new Exception\NoSuchValidatorException(sprintf('The validator "%s" does not implement %s!', $validatorObjectName, ValidatorInterface::class), 1300694875);
        }

        return $validator;
    }

    /**
     * Resolves and returns the base validator conjunction for the given data type.
     *
     * If no validation is necessary, the returned validator is empty.
     *
     * @param string $targetClassName Fully qualified class name of the target class, ie. the class which should be validated
     * @param array $validationGroups The validation groups to build the validator for
     * @return ConjunctionValidator The validator conjunction
     * @api
     */
    public function getBaseValidatorConjunction($targetClassName, array $validationGroups = ['Default'])
    {
        $targetClassName = trim($targetClassName, ' \\');
        $indexKey = $targetClassName . '##' . implode('##', $validationGroups);
        if (!array_key_exists($indexKey, $this->baseValidatorConjunctions)) {
            $this->buildBaseValidatorConjunction($indexKey, $targetClassName, $validationGroups);
        }

        return $this->baseValidatorConjunctions[$indexKey];
    }

    /**
     * Detects and registers any validators for arguments:
     * - by the data type specified in the param annotations
     * - additional validators specified in the validate annotations of a method
     *
     * @param string $className
     * @param string $methodName
     * @param array $methodParameters Optional pre-compiled array of method parameters
     * @param array $methodValidateAnnotations Optional pre-compiled array of validate annotations (as array)
     * @return array An Array of ValidatorConjunctions for each method parameters.
     * @throws Exception\InvalidValidationConfigurationException
     * @throws Exception\NoSuchValidatorException
     * @throws Exception\InvalidTypeHintException
     */
    public function buildMethodArgumentsValidatorConjunctions($className, $methodName, array $methodParameters = null, array $methodValidateAnnotations = null)
    {
        $validatorConjunctions = [];

        if ($methodParameters === null) {
            $methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
        }
        if (count($methodParameters) === 0) {
            return $validatorConjunctions;
        }

        foreach ($methodParameters as $parameterName => $methodParameter) {
            $validatorConjunction = $this->createValidator(ConjunctionValidator::class);

            if (!array_key_exists('type', $methodParameter)) {
                throw new Exception\InvalidTypeHintException('Missing type information, probably no @param annotation for parameter "$' . $parameterName . '" in ' . $className . '->' . $methodName . '()', 1281962564);
            }
            if (strpos($methodParameter['type'], '\\') === false) {
                $typeValidator = $this->createValidator($methodParameter['type']);
            } else {
                $typeValidator = null;
            }

            if ($typeValidator !== null) {
                $validatorConjunction->addValidator($typeValidator);
            }
            $validatorConjunctions[$parameterName] = $validatorConjunction;
        }

        if ($methodValidateAnnotations === null) {
            $validateAnnotations = $this->reflectionService->getMethodAnnotations($className, $methodName, Flow\Validate::class);
            $methodValidateAnnotations = array_map(function ($validateAnnotation) {
                return [
                    'type' => $validateAnnotation->type,
                    'options' => $validateAnnotation->options,
                    'argumentName' => $validateAnnotation->argumentName,
                ];
            }, $validateAnnotations);
        }

        foreach ($methodValidateAnnotations as $annotationParameters) {
            $newValidator = $this->createValidator($annotationParameters['type'], $annotationParameters['options']);
            if ($newValidator === null) {
                throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Could not resolve class name for  validator "' . $annotationParameters['type'] . '".', 1239853109);
            }
            if (isset($validatorConjunctions[$annotationParameters['argumentName']])) {
                $validatorConjunctions[$annotationParameters['argumentName']]->addValidator($newValidator);
            } elseif (strpos($annotationParameters['argumentName'], '.') !== false) {
                $objectPath = explode('.', $annotationParameters['argumentName']);
                $argumentName = array_shift($objectPath);
                $validatorConjunctions[$argumentName]->addValidator($this->buildSubObjectValidator($objectPath, $newValidator));
            } else {
                throw new Exception\InvalidValidationConfigurationException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Validator specified for argument name "' . $annotationParameters['argumentName'] . '", but this argument does not exist.', 1253172726);
            }
        }

        return $validatorConjunctions;
    }

    /**
     * Resets the baseValidatorConjunctions
     * It is usually not required to reset the ValidatorResolver during one request. This method is mainly useful for functional tests
     *
     * @return void
     */
    public function reset()
    {
        $this->baseValidatorConjunctions = [];
    }

    /**
     * Builds a chain of nested object validators by specification of the given
     * object path.
     *
     * @param array $objectPath The object path
     * @param ValidatorInterface $propertyValidator The validator which should be added to the property specified by objectPath
     * @return GenericObjectValidator
     */
    protected function buildSubObjectValidator(array $objectPath, ValidatorInterface $propertyValidator)
    {
        $rootObjectValidator = new GenericObjectValidator([]);
        $parentObjectValidator = $rootObjectValidator;

        while (count($objectPath) > 1) {
            $subObjectValidator = new GenericObjectValidator([]);
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
     * Example: $targetClassName is Neos\Foo\Domain\Model\Quux, then the validator will be found if it has the
     * name Neos\Foo\Domain\Validator\QuuxValidator
     *
     * @param string $indexKey The key to use as index in $this->baseValidatorConjunctions; calculated from target class name and validation groups
     * @param string $targetClassName The data type to build the validation conjunction for. Needs to be the fully qualified class name.
     * @param array $validationGroups The validation groups to build the validator for
     * @return void
     * @throws Exception\NoSuchValidatorException
     * @throws \InvalidArgumentException
     */
    protected function buildBaseValidatorConjunction($indexKey, $targetClassName, array $validationGroups)
    {
        $conjunctionValidator = new ConjunctionValidator();
        $this->baseValidatorConjunctions[$indexKey] = $conjunctionValidator;
        if (!TypeHandling::isSimpleType($targetClassName) && class_exists($targetClassName)) {
            // Model based validator
            $classSchema = $this->reflectionService->getClassSchema($targetClassName);
            if ($classSchema !== null && $classSchema->isAggregateRoot()) {
                $objectValidator = new AggregateBoundaryValidator(array());
            } else {
                $objectValidator = new GenericObjectValidator([]);
            }
            $conjunctionValidator->addValidator($objectValidator);
            foreach ($this->reflectionService->getClassPropertyNames($targetClassName) as $classPropertyName) {
                $classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($targetClassName, $classPropertyName);

                if (!isset($classPropertyTagsValues['var'])) {
                    throw new \InvalidArgumentException(sprintf('There is no @var annotation for property "%s" in class "%s".', $classPropertyName, $targetClassName), 1363778104);
                }
                try {
                    $parsedType = TypeHandling::parseType(trim(implode('', $classPropertyTagsValues['var']), ' \\'));
                } catch (InvalidTypeException $exception) {
                    throw new \InvalidArgumentException(sprintf(' @var annotation of ' . $exception->getMessage(), 'class "' . $targetClassName . '", property "' . $classPropertyName . '"'), 1315564744, $exception);
                }

                if ($this->reflectionService->isPropertyAnnotatedWith($targetClassName, $classPropertyName, Flow\IgnoreValidation::class)) {
                    continue;
                }

                $propertyTargetClassName = $parsedType['type'];
                if (TypeHandling::isCollectionType($propertyTargetClassName) === true) {
                    $collectionValidator = $this->createValidator(Validator\CollectionValidator::class, ['elementType' => $parsedType['elementType'], 'validationGroups' => $validationGroups]);
                    $objectValidator->addPropertyValidator($classPropertyName, $collectionValidator);
                } elseif (!TypeHandling::isSimpleType($propertyTargetClassName) && $this->objectManager->isRegistered($propertyTargetClassName) && $this->objectManager->getScope($propertyTargetClassName) === Configuration::SCOPE_PROTOTYPE) {
                    $validatorForProperty = $this->getBaseValidatorConjunction($propertyTargetClassName, $validationGroups);
                    if (count($validatorForProperty) > 0) {
                        $objectValidator->addPropertyValidator($classPropertyName, $validatorForProperty);
                    }
                }

                $validateAnnotations = $this->reflectionService->getPropertyAnnotations($targetClassName, $classPropertyName, Flow\Validate::class);
                foreach ($validateAnnotations as $validateAnnotation) {
                    if (count(array_intersect($validateAnnotation->validationGroups, $validationGroups)) === 0) {
                        // In this case, the validation groups for the property do not match current validation context
                        continue;
                    }
                    $newValidator = $this->createValidator($validateAnnotation->type, $validateAnnotation->options);
                    if ($newValidator === null) {
                        throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $targetClassName . '::' . $classPropertyName . ': Could not resolve class name for  validator "' . $validateAnnotation->type . '".', 1241098027);
                    }
                    $objectValidator->addPropertyValidator($classPropertyName, $newValidator);
                }
            }
            if (count($objectValidator->getPropertyValidators()) === 0) {
                $conjunctionValidator->removeValidator($objectValidator);
            }
        }

        $this->addCustomValidators($targetClassName, $conjunctionValidator);
    }

    /**
     * This adds custom validators to the passed $conjunctionValidator.
     *
     * A custom validator is found if it follows the naming convention "Replace '\Model\' by '\Validator\' and
     * append 'Validator'". If found, it will be added to the $conjunctionValidator.
     *
     * In addition canValidate() will be called on all implementations of the ObjectValidatorInterface to find
     * all validators that could validate the target. The one with the highest priority will be added as well.
     * If multiple validators have the same priority, which one will be added is not deterministic.
     *
     * @param string $targetClassName
     * @param ConjunctionValidator $conjunctionValidator
     * @return NULL|Validator\ObjectValidatorInterface
     */
    protected function addCustomValidators($targetClassName, ConjunctionValidator &$conjunctionValidator)
    {
        // Custom validator for the class
        $addedValidatorClassName = null;
        $possibleValidatorClassName = str_replace('\\Model\\', '\\Validator\\', $targetClassName) . 'Validator';
        $customValidator = $this->createValidator($possibleValidatorClassName);
        if ($customValidator !== null) {
            $conjunctionValidator->addValidator($customValidator);
            $addedValidatorClassName = get_class($customValidator);
        }

        // find polytype validator for class
        $acceptablePolyTypeValidators = [];
        $polyTypeObjectValidatorImplementationClassNames = static::getPolyTypeObjectValidatorImplementationClassNames($this->objectManager);
        foreach ($polyTypeObjectValidatorImplementationClassNames as $validatorImplementationClassName) {
            $acceptablePolyTypeValidator = $this->createValidator($validatorImplementationClassName);
            // skip custom validator already added above
            if ($addedValidatorClassName === get_class($acceptablePolyTypeValidator)) {
                continue;
            }
            if ($acceptablePolyTypeValidator->canValidate($targetClassName)) {
                $acceptablePolyTypeValidators[$acceptablePolyTypeValidator->getPriority()] = $acceptablePolyTypeValidator;
            }
        }
        if (count($acceptablePolyTypeValidators) > 0) {
            ksort($acceptablePolyTypeValidators);
            $conjunctionValidator->addValidator(array_pop($acceptablePolyTypeValidators));
        }
    }

    /**
     * Returns a map of object validator class names.
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of object validator class names
     * @Flow\CompileStatic
     */
    public static function getPolyTypeObjectValidatorImplementationClassNames($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);
        $result = $reflectionService->getAllImplementationClassNamesForInterface(Validator\PolyTypeObjectValidatorInterface::class);
        return $result;
    }

    /**
     * Returns the class name of an appropriate validator for the given type. If no
     * validator is available FALSE is returned
     *
     * @param string $validatorType Either the fully qualified class name of the validator or the short name of a built-in validator
     * @return string|boolean Class name of the validator or FALSE if not available
     */
    protected function resolveValidatorObjectName($validatorType)
    {
        $validatorType = ltrim($validatorType, '\\');

        $validatorClassNames = static::getValidatorImplementationClassNames($this->objectManager);

        if ($this->objectManager->isRegistered($validatorType) && isset($validatorClassNames[$validatorType])) {
            return $validatorType;
        }

        if (strpos($validatorType, ':') !== false) {
            list($packageName, $packageValidatorType) = explode(':', $validatorType);
            $possibleClassName = sprintf('%s\Validation\Validator\%sValidator', str_replace('.', '\\', $packageName), $this->getValidatorType($packageValidatorType));
        } else {
            $possibleClassName = sprintf('Neos\Flow\Validation\Validator\%sValidator', $this->getValidatorType($validatorType));
        }
        if ($this->objectManager->isRegistered($possibleClassName) && isset($validatorClassNames[$possibleClassName])) {
            return $possibleClassName;
        }

        return false;
    }

    /**
     * Returns all class names implementing the ValidatorInterface.
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of class names implementing ValidatorInterface indexed by class name
     * @Flow\CompileStatic
     */
    public static function getValidatorImplementationClassNames($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);
        $classNames = $reflectionService->getAllImplementationClassNamesForInterface(ValidatorInterface::class);
        return array_flip($classNames);
    }

    /**
     * Used to map PHP types to validator types.
     *
     * @param string $type Data type to unify
     * @return string unified data type
     */
    protected function getValidatorType($type)
    {
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
