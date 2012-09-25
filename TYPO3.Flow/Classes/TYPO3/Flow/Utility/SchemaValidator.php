<?php
namespace TYPO3\Flow\Utility;

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

/**
 * A general purpose Array Validator which can check PHP arrays for validity
 * according to some schema.
 *
 * The schema format is adapted from the JSON Schema standard (http://json-schema.org)
 *
 * Currently the Parts 5.1 -> 5.25 of the json-schema spec are implemented.
 *
 * Variations from the spec:
 * - The "type" constraint is required for all properties.
 * - The validator only executes the checks that make sense for a specific type
 *   -> see list of possible contstraints below.
 * - The "format" constraint for string type has additional class-name and instance-name options.
 * - The "dependencies" constraint of the spec is not implemented.
 * - Similar to "patternProperties" "formatProperties" can be specified specified for dictionaries
 *
 * General constraints for all types (for implementation see validate Method):
 * - type
 * - disallow
 * - enum
 *
 * Additional Constraints for types:
 * - string: pattern, minLength, maxLength, format(date-time|date|time|uri|email|ipv4|ipv6|ip-address|host-name|class-name|interface-name)
 * - number: maximum, minimum, exclusiveMinimum, exclusiveMaximum, divisibleBy
 * - integer: maximum, minimum, exclusiveMinimum, exclusiveMaximum, divisibleBy
 * - boolean: --
 * - array: minItems, maxItems, items
 * - dictionary: properties, patternProperties, formatProperties, additionalProperties
 * - null: --
 * - any: --
 *
 * @Flow\Scope("singleton")
 */
class SchemaValidator {

	/**
	 * Validate array with the given schema
	 *
	 * The following properties are handled in given $schema:
	 * - type : value is of given type or schema (array of schemas is allowed)
	 * - disallow : value is NOT of given type or schema (array of schemas is allowed)
	 * - enum : value is equal to one of the given values
	 *
	 * @param mixed $value value to validate
	 * @param mixed $schema type as string, schema or array of schemas
	 * @return \TYPO3\Flow\Error\Result
	 */
	public function validate($value, $schema) {
		$result = new \TYPO3\Flow\Error\Result();

		if (is_string($schema) === TRUE) {
			$result->merge($this->validateType($value, array('type'=>$schema)));
		} elseif ($this->isNumericallyIndexedArray($schema)) {
			$isValid = FALSE;
			foreach ($schema as $singleSchema) {
				$singleResult = $this->validate($value, $singleSchema);
				if ($singleResult->hasErrors() === FALSE){
					$isValid = TRUE;
				}
			}
			if ($isValid == FALSE){
				$result->addError($this->createError('None of the given schemas matched ' . $value));
			}
		} elseif ($this->isSchema($schema)) {
			if (isset($schema['type'])) {
				if (is_array($schema['type'])){
					$result->merge($this->validateTypeArray($value, $schema));
				} else {
					$result->merge($this->validateType($value, $schema));
				}
			}

			if (isset($schema['disallow'])) {
				$subresult = $this->validate($value, array('type' => $schema['disallow']));
				if ($subresult->hasErrors() === FALSE) {
					$result->addError($this->createError('Disallow rule matched for "' . $value . '"'));
				}
			}

			if (isset($schema['enum'])) {
				$isValid = FALSE;
				foreach ($schema['enum'] as $allowedValue) {
					if ($value === $allowedValue) {
						$isValid = TRUE;
						break;
					}
				}
				if ($isValid === FALSE) {
					$result->addError($this->createError('"' . $value . '" is not in enum-rule "' . implode(', ', $schema['enum']) . '"'));
				}
			}
		}

		return $result;
	}

	/**
	 * Validate a value for a given type
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateType($value, array $schema) {
		$result = new \TYPO3\Flow\Error\Result();
		if (isset($schema['type'])) {
			switch ($schema['type']) {
				case 'string':
					$result->merge( $this->validateStringType($value, $schema));
					break;
				case 'number':
					$result->merge( $this->validateNumberType($value, $schema));
					break;
				case 'integer':
					$result->merge($this->validateIntegerType($value, $schema));
					break;
				case 'boolean':
					$result->merge($this->validateBooleanType($value, $schema));
					break;
				case 'array':
					$result->merge($this->validateArrayType($value, $schema));
					break;
				case 'dictionary':
					$result->merge($this->validateDictionaryType($value, $schema));
					break;
				case 'null':
					$result->merge($this->validateNullType($value, $schema));
					break;
				case 'any':
					$result->merge($this->validateAnyType($value, $schema));
					break;
				default:
					$result->addError($this->createError('Type "' . $schema['type'] . '" is unknown'));
					break;
			}
		} else {
			$result->addError($this->createError('Type constraint is required'));
		}

		return $result;
	}

	/**
	 * Validate a value with a given list of allowed types
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateTypeArray($value, array $schema) {
		$result = new \TYPO3\Flow\Error\Result();
		$isValid = FALSE;
		foreach ($schema['type'] as $type) {
			$partResult = $this->validate($value, $type);
			if ($partResult->hasErrors() === FALSE) {
				$isValid = TRUE;
			}
		}
		if ($isValid === FALSE) {
			$result->addError($this->createError('type=' . (is_array($schema['type']) ? implode(', ', $schema['type']) : $schema['type']), 'type=' . gettype($value)));
		}
		return $result;
	}

	/**
	 * Validate an integer value with the given schema
	 *
	 * The following properties are handled in given $schema:
	 * - maximum : maximum allowed value
	 * - minimum : minimum allowed value
	 * - exclusiveMinimum : boolean to use exclusive minimum
	 * - exclusiveMaximum : boolean to use exclusive maximum
	 * - divisibleBy : value is divisibleBy the given number
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateNumberType($value, array $schema) {

		$result = new \TYPO3\Flow\Error\Result();
		if (is_numeric($value) === FALSE) {
			$result->addError($this->createError('type=number', 'type=' . gettype($value)));
			return $result;
		}

		if (isset($schema['maximum'])) {
			if (isset($schema['exclusiveMaximum']) && $schema['exclusiveMaximum'] === TRUE) {
				if ($value >= $schema['maximum']) {
					$result->addError($this->createError('maximum(exclusive)=' .$schema['maximum'], $value));
				}
			} else {
				if ($value > $schema['maximum']) {
					$result->addError($this->createError('maximum=' .$schema['maximum'] , $value));
				}
			}
		}

		if (isset($schema['minimum'])) {
			if (isset($schema['exclusiveMinimum']) && $schema['exclusiveMinimum'] === TRUE) {
				if ($value <= $schema['minimum']) {
					$result->addError($this->createError('minimum(exclusive)=' .$schema['minimum'], $value));
				}
			} else {
				if ($value < $schema['minimum']) {
					$result->addError($this->createError('minimum=' .$schema['minimum'], $value));
				}
			}
		}

		if (isset($schema['divisibleBy']) && $value % $schema['divisibleBy'] !== 0) {
			$result->addError($this->createError('divisibleBy=' . $schema['divisibleBy'], $value));
		}

		return $result;
	}

	/**
	 *
	 * Validate an integer value with the given schema
	 *
	 * The following properties are handled in given $schema:
	 * - all Properties from number type
	 *
	 * @see TYPO3\Flow\Utility\schemaValidator\validateNumberType
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateIntegerType($value, array $schema) {
		$result = new \TYPO3\Flow\Error\Result();
		if (is_integer($value) === FALSE) {
			$result->addError($this->createError('type=integer', 'type=' . gettype($value)));
			return $result;
		}
		$result->merge($this->validateNumberType($value, $schema));
		return $result;
	}

	/**
	 * Validate a boolean value with the given schema
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateBooleanType($value, array $schema){
		$result = new \TYPO3\Flow\Error\Result();
		if (is_bool($value) === FALSE) {
			$result->addError($this->createError('type=boolean', 'type=' . gettype($value)));
			return $result;
		}
		return $result;
	}

	/**
	 * Validate an array value with the given schema
	 *
	 * The following properties are handled in given $schema:
	 * - minItems : minimal allowed item Number
	 * - maxItems : maximal allowed item Number
	 * - items : schema for all instances of the array
	 * - uniqueItems : allow only unique values
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateArrayType($value, array $schema){
		$result = new \TYPO3\Flow\Error\Result();
		if (is_array($value) === FALSE || $this->isNumericallyIndexedArray($value) === FALSE) {
			$result->addError($this->createError('type=array', 'type=' . gettype($value)));
			return $result;
		}

		if (isset($schema['minItems']) && count($value) < $schema['minItems']) {
			$result->addError($this->createError('minItems=' . $schema['minItems'], count($value) . ' items'));
		}

		if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) {
			$result->addError($this->createError('maxItems=' . $schema['maxItems'], count($value) . ' items'));
		}

		if (isset($schema['items'])) {
			foreach ($value as $index => $itemValue) {
				$itemResult = $this->validate($itemValue, $schema['items']);
				if ($itemResult->hasErrors()  === TRUE ) {
					$result->forProperty('__index_' . $index)->merge($itemResult);
				}
			}
		}

		if (isset($schema['uniqueItems']) && $schema['uniqueItems'] === TRUE) {
			$values = array();
			foreach ($value as $itemValue) {
				$itemHash = is_array($itemValue) ? serialize($itemValue) : $itemValue;
				if (in_array($itemHash, $values)){
					$result->addError($this->createError('Unique values are expected'));
					break;
				} else {
					$values[] = $itemHash;
				}
			}
		}

		return $result;
	}

	/**
	 * Validate a dictionary value with the given schema
	 *
	 * The following properties are handled in given $schema:
	 * - properties : array of keys and schemas that have to validate
	 * - formatProperties : dictionary of schemas, the schemas are used to validate all keys that match the string-format
	 * - patternProperties : dictionary of schemas, the schemas are used to validate all keys that match the string-pattern
	 * - additionalProperties : if FALSE is given all additionalProperties are forbidden, if a schema is given all additional properties have to match the schema
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateDictionaryType($value, array $schema) {
		$result = new \TYPO3\Flow\Error\Result();
		if (is_array($value) === FALSE || $this->isDictionary($value) === FALSE) {
			$result->addError($this->createError('type=dictionary', 'type=' . gettype($value)));
			return $result;
		}

		$propertyKeysToHandle = array_keys($value);

		if (isset($schema['properties'])) {
			foreach ($schema['properties'] as $propertyName => $propertySchema){
				if (array_key_exists($propertyName, $value)) {
					$propertyValue = $value[$propertyName];
					$subresult = $this->validate($propertyValue, $propertySchema);
					if ($subresult->hasErrors()) {
						$result->forProperty($propertyName)->merge($subresult);
					}
					$propertyKeysToHandle = array_diff($propertyKeysToHandle, array($propertyName));
				} else {
						// is subproperty required
					if(is_array($propertySchema) && $this->isSchema($propertySchema) && isset($propertySchema['required']) && $propertySchema['required'] == TRUE) {
						$result->addError($this->createError('Property ' . $propertyName . ' is required'));
					}
				}
			}
		}

		if (isset($schema['patternProperties']) && count($propertyKeysToHandle) > 0 && $this->isDictionary($schema['patternProperties'])) {
			foreach (array_values($propertyKeysToHandle) as $propertyKey){
				foreach ($schema['patternProperties'] as $propertyPattern => $propertySchema){
					$keyResult = $this->validateStringType($propertyKey, array('pattern' => $propertyPattern));
					if ($keyResult->hasErrors() === FALSE) {
						$subresult = $this->validate($value[$propertyKey], $propertySchema);
						if ($subresult->hasErrors()){
							$result->forProperty($propertyKey)->merge($subresult);
						}
						$propertyKeysToHandle = array_diff($propertyKeysToHandle, array($propertyKey));
					}
				}
			}
		}

		if (isset($schema['formatProperties']) && count($propertyKeysToHandle) > 0 && $this->isDictionary($schema['formatProperties'])) {
			foreach (array_values($propertyKeysToHandle) as $propertyKey){
				foreach ($schema['formatProperties'] as $propertyPattern => $propertySchema){
					$keyResult = $this->validateStringType($propertyKey, array('format' => $propertyPattern));
					if ($keyResult->hasErrors() === FALSE) {
						$subresult = $this->validate($value[$propertyKey], $propertySchema);
						if ($subresult->hasErrors()){
							$result->forProperty($propertyKey)->merge($subresult);
						}
						$propertyKeysToHandle = array_diff($propertyKeysToHandle, array($propertyKey));
					}
				}
			}
		}

		if (isset($schema['additionalProperties']) && count($propertyKeysToHandle) > 0) {
			if ($schema['additionalProperties'] === FALSE) {
				foreach ($propertyKeysToHandle as $propertyKey) {
					$result->forProperty($propertyKey)->addError($this->createError('This property is not allowed here, check the spelling if you think it belongs here.'));
				}
			} else {
				foreach ($propertyKeysToHandle as $propertyKey) {
					$subresult = $this->validate($value[$propertyKey], $schema['additionalProperties']);
					if ($subresult->hasErrors()){
						$result->forProperty($propertyKey)->merge($subresult);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Validate a string value with the given schema
	 *
	 * The following properties are handled in given $schema:
	 *
	 * - pattern : Regular expression that matches the $value
	 * - minLength : minimal allowed string length
	 * - maxLength : maximal allowed string length
	 * - format : some predefined formats
	 *   [date-time|date|time|uri|email|ipv4|ipv6|ip-address|host-name|class-name|interface-name]
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateStringType($value, array $schema) {
		$result = new \TYPO3\Flow\Error\Result();
		if (is_string($value) === FALSE) {
			$result->addError($this->createError('type=string', 'type=' . gettype($value)));
			return $result;
		}

		if (isset($schema['pattern'])) {
			if (preg_match($schema['pattern'], $value) === 0) {
				$result->addError($this->createError('pattern=' . $schema['pattern'], $value));
			}
		}

		if (isset($schema['minLength'])) {
			if (strlen($value) < (int)$schema['minLength']) {
				$result->addError($this->createError('minLength=' . $schema['minLength'], 'strlen=' . strlen($value)));
			}
		}

		if (isset($schema['maxLength'])) {
			if (strlen($value) > (int)$schema['maxLength']) {
				$result->addError($this->createError('maxLength=' . $schema['maxLength'], 'strlen=' . strlen($value)));
			}
		}

		if (isset($schema['format'])) {
			switch ($schema['format']) {
				case 'date-time':
						// YYYY-MM-DDThh:mm:ssZ ISO8601
					\DateTime::createFromFormat(\DateTime::ISO8601, $value);
					$parseErrors = \DateTime::getLastErrors();
					if ($parseErrors['error_count'] > 0) {
						$result->addError($this->createError('format=datetime', $value));
					}
					break;
				case 'date':
						// YYYY-MM-DD
					\DateTime::createFromFormat('Y-m-d', $value);
					$parseErrors = \DateTime::getLastErrors();
					if ($parseErrors['error_count'] > 0) {
						$result->addError($this->createError('format=date', $value));
					}
					break;
				case 'time':
						// hh:mm:ss
					\DateTime::createFromFormat('H:i:s', $value);
					$parseErrors = \DateTime::getLastErrors();
					if ($parseErrors['error_count'] > 0) {
						$result->addError($this->createError('format=time', $value));
					}
					break;
				case 'uri':
					if (filter_var($value, FILTER_VALIDATE_URL) === FALSE) {
						$result->addError($this->createError('format=uri', $value));
					}
					break;
				case 'email':
					if (filter_var($value, FILTER_VALIDATE_EMAIL) === FALSE) {
						$result->addError($this->createError('format=email', $value));
					}
					break;
				case 'ipv4':
					if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === FALSE) {
						$result->addError($this->createError('format=ipv4', $value));
					}
					break;
				case 'ipv6':
					if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === FALSE) {
						$result->addError($this->createError('format=ipv6', $value));
					}
					break;
				case 'ip-address':
					if (filter_var($value, FILTER_VALIDATE_IP) === FALSE) {
						$result->addError($this->createError('format=ip-address', $value));
					}
					break;
				case 'host-name':
					if (gethostbyname($value) === $value) {
						$result->addError($this->createError('format=host-name', $value));
					}
					break;
				case 'class-name':
					if (class_exists($value) === FALSE) {
						$result->addError($this->createError('format=class-name', $value));
					}
					break;
				case 'interface-name':
					if (interface_exists($value) === FALSE) {
						$result->addError($this->createError('format=interface-name', $value));
					}
					break;
				default:
					$result->addError($this->createError('Expected string-format "' . $schema['format'] . '" is unknown'));
					break;
			}
		}
		return $result;
	}

	/**
	 * Validate a null value with the given schema
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateNullType($value, array $schema) {
		$result = new \TYPO3\Flow\Error\Result();
		if ($value !== NULL) {
			$result->addError($this->createError('type=NULL', 'type=' . gettype($value)));
		}
		return $result;
	}

	/**
	 * Validate any value with the given schema. Return always a valid Result.
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @return \TYPO3\Flow\Error\Result
	 */
	protected function validateAnyType($value, array $schema) {
		return new \TYPO3\Flow\Error\Result();
	}

	/**
	 * Create Error Object
	 *
	 * @param string $expectation
	 * @param string $value
	 * @return \TYPO3\Flow\Error\Error
	 */
	protected function createError($expectation, $value = NULL) {
		if ($value !== NULL) {
			$error = new \TYPO3\Flow\Error\Error('expected: %s found: %s', 1328557141, array($expectation, $value), 'Validation Error');
		} else {
			$error = new \TYPO3\Flow\Error\Error($expectation, 1328557141, array(), 'Validation Error');
		}
		return $error;
	}

	/**
	 * Determine whether the given php array is a schema or not
	 *
	 * @todo there should be a more sophisticated way to detect schemas
	 * @param array $phpArray
	 * @return boolean
	 */
	protected function isSchema(array $phpArray) {
			// maybe we should validate against a schema here ;-)
		return $this->isDictionary($phpArray);
	}

	/**
	 * Determine whether the given php array is a plain numerically indexed array
	 *
	 * @param array $phpArray
	 * @return boolean
	 */
	protected function isNumericallyIndexedArray(array $phpArray) {
		foreach (array_keys($phpArray) as $key) {
			if (is_numeric($key) === FALSE) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Determine whether the given php array is a Dictionary (has no numeric identifiers)
	 *
	 * @param array $phpArray
	 * @return boolean
	 */
	protected function isDictionary(array $phpArray) {
		return array_keys($phpArray) !== range(0, count($phpArray) - 1);
	}
}
?>