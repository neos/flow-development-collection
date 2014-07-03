<?php
namespace TYPO3\Flow\Property\TypeConverter;

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
 * Converter which transforms simple types to a string.
 *
 * * If the source is a DateTime instance, it will be formatted as string. The format
 *   can be set via CONFIGURATION_DATE_FORMAT.
 * * If the source is an array, it will be converted to a CSV string or JSON, depending
 *   on CONFIGURATION_ARRAY_FORMAT.
 *
 * For array to CSV string, the delimiter can be set via CONFIGURATION_CSV_DELIMITER.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class StringConverter extends AbstractTypeConverter {

	/**
	 * @var string
	 */
	const CONFIGURATION_DATE_FORMAT = 'dateFormat';

	/**
	 * The default date format is "YYYY-MM-DDT##:##:##+##:##", for example "2005-08-15T15:52:01+00:00"
	 * according to the W3C standard @see http://www.w3.org/TR/NOTE-datetime.html
	 *
	 * @var string
	 */
	const DEFAULT_DATE_FORMAT = \DateTime::W3C;

	/**
	 * @var string
	 */
	const CONFIGURATION_ARRAY_FORMAT = 'arrayFormat';

	/**
	 * @var string
	 */
	const DEFAULT_ARRAY_FORMAT = self::ARRAY_FORMAT_CSV;

	/**
	 * @var string
	 */
	const ARRAY_FORMAT_CSV = 'csv';

	/**
	 * @var string
	 */
	const ARRAY_FORMAT_JSON = 'json';

	/**
	 * @var string
	 */
	const CONFIGURATION_CSV_DELIMITER = 'csvDelimiter';

	/**
	 * @var string
	 */
	const DEFAULT_CSV_DELIMITER = ',';

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string', 'integer', 'float', 'boolean', 'array', 'DateTime');

	/**
	 * @var string
	 */
	protected $targetType = 'string';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType, taking into account the fully
	 * built $convertedChildProperties and $configuration.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($source instanceof \DateTime) {
			$dateFormat = $this->getDateFormat($configuration);

			return $source->format($dateFormat);
		}

		if (is_array($source)) {
			switch ($this->getArrayFormat($configuration)) {
				case self::ARRAY_FORMAT_CSV:
					return implode($this->getCsvDelimiter($configuration), $source);
				case self::ARRAY_FORMAT_JSON:
					return json_encode($source);
				default:
					throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException(sprintf('Invalid array export format "%s" given', $this->getArrayFormat($configuration)), 1404317220);
			}
		}

		return (string)$source;
	}

	/**
	 * Determines the date format to use for the conversion.
	 *
	 * If no format is specified in the mapping configuration DEFAULT_DATE_FORMAT is used.
	 *
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function getDateFormat(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			return self::DEFAULT_DATE_FORMAT;
		}

		$dateFormat = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\StringConverter', self::CONFIGURATION_DATE_FORMAT);
		if ($dateFormat === NULL) {
			return self::DEFAULT_DATE_FORMAT;
		} elseif ($dateFormat !== NULL && !is_string($dateFormat)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_DATE_FORMAT must be of type string, "' . (is_object($dateFormat) ? get_class($dateFormat) : gettype($dateFormat)) . '" given', 1404229004);
		}

		return $dateFormat;
	}

	/**
	 * Determines the delimiter to use for the conversion from array to CSV format.
	 *
	 * If no delimiter is specified in the mapping configuration DEFAULT_CSV_DELIMITER is used.
	 *
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function getCsvDelimiter(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			return self::DEFAULT_CSV_DELIMITER;
		}

		$csvDelimiter = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\StringConverter', self::CONFIGURATION_CSV_DELIMITER);
		if ($csvDelimiter === NULL) {
			return self::DEFAULT_CSV_DELIMITER;
		} elseif (!is_string($csvDelimiter)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_CSV_DELIMITER must be of type string, "' . (is_object($csvDelimiter) ? get_class($csvDelimiter) : gettype($csvDelimiter)) . '" given', 1404229000);
		}

		return $csvDelimiter;
	}

	/**
	 * Determines the format to use for the conversion from array to string.
	 *
	 * If no format is specified in the mapping configuration DEFAULT_ARRAY_FORMAT is used.
	 *
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function getArrayFormat(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			return self::DEFAULT_ARRAY_FORMAT;
		}

		$arrayFormat = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\StringConverter', self::CONFIGURATION_ARRAY_FORMAT);
		if ($arrayFormat === NULL) {
			return self::DEFAULT_ARRAY_FORMAT;
		} elseif (!is_string($arrayFormat)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_ARRAY_FORMAT must be of type string, "' . (is_object($arrayFormat) ? get_class($arrayFormat) : gettype($arrayFormat)) . '" given', 1404228995);
		}

		return $arrayFormat;
	}
}
