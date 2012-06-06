<?php
namespace TYPO3\FLOW3\Utility;

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

/**
 * Configuration schema generator.
 *
 * The implementation is still simple and intends to be a kickstart for
 * writing schemas. In future this can be extended.
 *
 * See \TYPO3\FLOW3\Utility\SchemaValidator for a description of all features
 * of the SchemaValidator
 *
 * @FLOW3\Scope("singleton")
 */
class SchemaGenerator {

	/**
	 * Generate a schema for the given value
	 *
	 * @param mixed $value value to create a schema for
	 * @return array schema as array structure
	 */
	public function generate($value) {
		$schema = array();
		switch (gettype($value)) {
			case 'NULL':
				$schema['type'] = 'null';
				break;
			case 'boolean':
				$schema['type'] = 'boolean';
				break;
			case 'integer':
				$schema['type'] = 'integer';
				break;
			case 'double':
				$schema['type'] = 'number';
				break;
			case 'string':
				$schema = $this->generateStringSchema($value);
				break;
			case 'array':
				$isDictionary = array_keys($value) !== range(0, count($value) - 1);
				if ($isDictionary) {
					$schema = $this->generateDictionarySchema($value);
				} else {
					$schema = $this->generateArraySchema($value);
				}
				break;
		}
		return $schema;
	}

	/**
	 * Create a schema for a dictionary
	 *
	 * @param array $dictionaryValue
	 * @return array
	 */
	protected function generateDictionarySchema(array $dictionaryValue) {
		$schema = array('type' => 'dictionary', 'properties' => array());
		ksort($dictionaryValue);
		foreach ($dictionaryValue as $name => $subvalue) {
			$schema['properties'][$name] = $this->generate($subvalue);
		}
		return $schema;
	}

	/**
	 * Create a schema for an array structure
	 *
	 * @param array $arrayValue
	 * @return array schema
	 */
	protected function generateArraySchema(array $arrayValue) {
		$schema = array('type' => 'array');
		$subSchemas = array();
		foreach ($arrayValue as $subValue) {
			$subSchemas[] = $this->generate($subValue);
		}
		$schema['items'] = $this->filterDuplicatesFromArray($subSchemas);
		return $schema;
	}

	/**
	 * Create a schema for a given string
	 *
	 * @param string $stringValue
	 * @return array
	 */
	protected function generateStringSchema($stringValue) {
		$schema = array('type' => 'string');
		$schemaValidator = new \TYPO3\FLOW3\Utility\SchemaValidator();
		$detectedFormat = NULL;

		$detectableFormats = array('uri','email','ip-address','class-name','interface-name');
		foreach ($detectableFormats as $testFormat) {
			$testSchema = array('type' => 'string', 'format' => $testFormat);
			$result = $schemaValidator->validate($stringValue, $testSchema);
			if ($result->hasErrors() === FALSE) {
				$detectedFormat = $testFormat;
			}
		}
		if ($detectedFormat !== NULL) {
			$schema['format'] = $detectedFormat;
		}
		return $schema;
	}

	/**
	 * Compact an array of items to avoid adding the same value more than once.
	 * If the result contains only one item, that item is returned directly.
	 *
	 * @param array $values array of values
	 * @return mixed
	 */
	protected function filterDuplicatesFromArray(array $values) {
		$uniqueItems = array();
		foreach($values as $value){
			$uniqueItems[md5(serialize($value))] = $value;
		}
		$result = array_values($uniqueItems);
		if (count($result) == 1){
			return $result[0];
		} else {
			return $result;
		}
	}
}
?>