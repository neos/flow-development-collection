<?php
namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.Schema package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Configuration schema generator.
 *
 * The implementation is still simple and intends to be a kickstart for
 * writing schemas. In future this can be extended.
 *
 * See \Neos\Utility\SchemaValidator for a description of all features
 * of the SchemaValidator
 */
class SchemaGenerator
{
    /**
     * Generate a schema for the given value
     *
     * @param mixed $value value to create a schema for
     * @return array schema as array structure
     */
    public function generate($value)
    {
        $schema = [];
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
    protected function generateDictionarySchema(array $dictionaryValue)
    {
        $schema = ['type' => 'dictionary', 'properties' => []];
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
    protected function generateArraySchema(array $arrayValue)
    {
        $schema = ['type' => 'array'];
        $subSchemas = [];
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
    protected function generateStringSchema($stringValue)
    {
        $schema = ['type' => 'string'];
        $schemaValidator = new SchemaValidator();
        $detectedFormat = null;

        $detectableFormats = ['uri','email','ip-address','class-name','interface-name'];
        foreach ($detectableFormats as $testFormat) {
            $testSchema = ['type' => 'string', 'format' => $testFormat];
            $result = $schemaValidator->validate($stringValue, $testSchema);
            if ($result->hasErrors() === false) {
                $detectedFormat = $testFormat;
            }
        }
        if ($detectedFormat !== null) {
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
    protected function filterDuplicatesFromArray(array $values)
    {
        $uniqueItems = [];
        foreach ($values as $value) {
            $uniqueItems[md5(serialize($value))] = $value;
        }
        $result = array_values($uniqueItems);
        if (count($result) === 1) {
            return $result[0];
        } else {
            return $result;
        }
    }
}
