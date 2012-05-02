<?php
namespace TYPO3\FLOW3\Property;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Concrete configuration object for the PropertyMapper.
 *
 * @api
 */
class PropertyMappingConfiguration implements \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface {

	/**
	 * multi-dimensional array which stores type-converter specific configuration:
	 * 1. Dimension: Fully qualified class name of the type converter
	 * 2. Dimension: Configuration Key
	 * Value: Configuration Value
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * Stores the configuration for specific child properties.
	 *
	 * @var array<\TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface>
	 */
	protected $subConfigurationForProperty = array();

	/**
	 * Keys which should be renamed
	 *
	 * @var array
	 */
	protected $mapping = array();

	/**
	 * @var \TYPO3\FLOW3\Property\TypeConverterInterface
	 */
	protected $typeConverter = NULL;

	/**
	 * List of allowed property names to be converted
	 *
	 * @var array
	 */
	protected $propertiesToBeMapped = array();

	/**
	 * List of disallowed property names which will be ignored while property mapping
	 *
	 * @var array
	 */
	protected $propertiesNotToBeMapped = array();

	/**
	 * If TRUE, unknown properties will be mapped.
	 *
	 * @var boolean
	 */
	protected $mapUnknownProperties = FALSE;

	/**
	 * The behavior is as follows:
	 *
	 * - if a property has been explicitely forbidden using allowAllPropertiesExcept(...), it is directly rejected
	 * - if a property has been allowed using allowProperties(...), it is directly allowed.
	 * - if allowAllProperties* has been called, we allow unknown properties
	 * - else, return FALSE.
	 *
	 * @param string $propertyName
	 * @return TRUE if the given propertyName should be mapped, FALSE otherwise.
	 */
	public function shouldMap($propertyName) {
		if (isset($this->propertiesNotToBeMapped[$propertyName])) {
			return FALSE;
		}

		if (isset($this->propertiesToBeMapped[$propertyName])) {
			return TRUE;
		}

		return $this->mapUnknownProperties;
	}

	/**
	 * Allow all properties in property mapping, even unknown ones.
	 *
	 * @return void
	 * @api
	 */
	public function allowAllProperties() {
		$this->mapUnknownProperties = TRUE;
	}

	/**
	 * Allow a list of specific properties. All arguments of
	 * allowProperties are used here (varargs).
	 *
	 * Example: allowProperties('title', 'content', 'author')
	 *
	 * @param string $propertyName1
	 * @param string $propertyName2
	 * @param string $propertyName3 ...
	 * @return void
	 * @api
	 */
	public function allowProperties() {
		foreach (func_get_args() as $propertyName) {
			$this->propertiesToBeMapped[$propertyName] = $propertyName;
		}
	}

	/**
	 * Allow all properties during property mapping, but reject a few
	 * selected ones (blacklist).
	 *
	 * Example: allowAllPropertiesExcept('password', 'userGroup')
	 *
	 * @return void
	 * @api
	 */
	public function allowAllPropertiesExcept() {
		$this->mapUnknownProperties = TRUE;

		foreach (func_get_args() as $propertyName) {
			$this->propertiesNotToBeMapped[$propertyName] = $propertyName;
		}
	}

	/**
	 * Returns the sub-configuration for the passed $propertyName. Must ALWAYS return a valid configuration object!
	 *
	 * @param string $propertyName
	 * @return \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface the property mapping configuration for the given $propertyName.
	 * @api
	 */
	public function getConfigurationFor($propertyName) {
		if (isset($this->subConfigurationForProperty[$propertyName])) {
			return $this->subConfigurationForProperty[$propertyName];
		}

		return new \TYPO3\FLOW3\Property\PropertyMappingConfiguration();
	}

	/**
	 * Maps the given $sourcePropertyName to a target property name.
	 *
	 * @param string $sourcePropertyName
	 * @return string property name of target
	 * @api
	 */
	public function getTargetPropertyName($sourcePropertyName) {
		if (isset($this->mapping[$sourcePropertyName])) {
			return $this->mapping[$sourcePropertyName];
		}
		return $sourcePropertyName;
	}

	/**
	 * @param string $typeConverterClassName
	 * @param string $key
	 * @return mixed configuration value for the specific $typeConverterClassName. Can be used by Type Converters to fetch converter-specific configuration.
	 * @api
	 */
	public function getConfigurationValue($typeConverterClassName, $key) {
		if (!isset($this->configuration[$typeConverterClassName][$key])) {
			return NULL;
		}

		return $this->configuration[$typeConverterClassName][$key];
	}

	/**
	 * Define renaming from Source to Target property.
	 *
	 * @param string $sourcePropertyName
	 * @param string $targetPropertyName
	 * @return void
	 * @api
	 */
	public function setMapping($sourcePropertyName, $targetPropertyName) {
		$this->mapping[$sourcePropertyName] = $targetPropertyName;
	}

	/**
	 * Set all options for the given $typeConverter.
	 * @param string $typeConverter class name of type converter
	 * @param array $options
	 * @return void
	 * @api
	 */
	public function setTypeConverterOptions($typeConverter, array $options) {
		$this->configuration[$typeConverter] = $options;
	}

	/**
	 * Set a single option (denoted by $optionKey) for the given $typeConverter.
	 *
	 * @param string $typeConverter class name of type converter
	 * @param string $optionKey
	 * @param mixed $optionValue
	 * @return void
	 * @api
	 */
	public function setTypeConverterOption($typeConverter, $optionKey, $optionValue) {
		$this->configuration[$typeConverter][$optionKey] = $optionValue;
	}

	/**
	 * Returns the configuration for the specific property path, ready to be modified. Should be used
	 * inside a fluent interface like:
	 * $configuration->forProperty('foo.bar')->setTypeConverterOption(....)
	 *
	 * @param string $propertyPath
	 * @return \TYPO3\FLOW3\Property\PropertyMappingConfiguration (or a subclass thereof)
	 * @api
	 */
	public function forProperty($propertyPath) {
		$splittedPropertyPath = explode('.', $propertyPath);
		return $this->traverseProperties($splittedPropertyPath);
	}

	/**
	 * Traverse the property configuration. Only used by forProperty().
	 *
	 * @param array $splittedPropertyPath
	 * @return \TYPO3\FLOW3\Property\PropertyMappingConfiguration (or a subclass thereof)
	 */
	public function traverseProperties(array $splittedPropertyPath) {
		if (count($splittedPropertyPath) === 0) {
			return $this;
		}

		$currentProperty = array_shift($splittedPropertyPath);
		if (!isset($this->subConfigurationForProperty[$currentProperty])) {
			$type = get_class($this);
			$this->subConfigurationForProperty[$currentProperty] = new $type;
		}
		return $this->subConfigurationForProperty[$currentProperty]->traverseProperties($splittedPropertyPath);
	}

	/**
	 * Return the type converter set for this configuration.
	 *
	 * @return \TYPO3\FLOW3\Property\TypeConverterInterface
	 * @api
	 */
	public function getTypeConverter() {
		return $this->typeConverter;
	}

	/**
	 * Set a type converter which should be used for this specific conversion.
	 *
	 * @param \TYPO3\FLOW3\Property\TypeConverterInterface $typeConverter
	 * @return void
	 * @api
	 */
	public function setTypeConverter(\TYPO3\FLOW3\Property\TypeConverterInterface $typeConverter) {
		$this->typeConverter = $typeConverter;
	}
}
?>