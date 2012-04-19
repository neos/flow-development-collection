<?php
namespace TYPO3\FLOW3\Property\TypeConverter;

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
use TYPO3\FLOW3\Error\Error;
use TYPO3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader;

/**
 * Converter which transforms a simple type to a float.
 *
 * This is basically done by simply casting it, except you provide some configuration options
 * which will make this converter use FLOW3's locale parsing capabilities in order to respect
 * deviating decimal separators.
 *
 * **Advanced usage in action controller context**
 *
 * *Using default locale*::
 *
 *  public function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		'TYPO3\FLOW3\Property\TypeConverter\FloatConverter', 'locale', TRUE
 *  	);
 *  }
 *
 * Just providing TRUE as option value will use the current default locale. In case that default locale is "DE"
 * for Germany for example, where a comma is used as decimal separator, the mentioned code will return
 * (float)15.5 when the input was (string)"15,50".
 *
 * *Using arbitrary locale*::
 *
 *  public function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		'TYPO3\FLOW3\Property\TypeConverter\FloatConverter', 'locale', 'fr'
 *  	);
 *  }
 *
 * **Parsing mode**
 *
 * There are two parsing modes available, strict and lenient mode. Strict mode will check all constraints of the provided
 * format, and if any of them are not fulfilled, the conversion will not take place.
 * In Lenient mode the parser will try to extract the intended number from the string, even if it's not well formed.
 * Default for strict mode is TRUE.
 *
 * *Example setting lenient mode (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	'TYPO3\FLOW3\Property\TypeConverter\FloatConverter', 'strictMode', FALSE
 *  );
 *
 * **Format type**
 *
 * Format type can be decimal, percent or currency; represented as class constant FORMAT_TYPE_DECIMAL,
 * FORMAT_TYPE_PERCENT or FORMAT_TYPE_CURRENCY of class TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader.
 * Default, if none given, is FORMAT_TYPE_DECIMAL.
 *
 * *Example setting format type `currency` (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	'TYPO3\FLOW3\Property\TypeConverter\FloatConverter', 'formatType', \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_CURRENCY
 *  );
 *
 * **Format length**
 *
 * Format type can be default, full, long, medium or short; represented as class constant FORMAT_LENGTH_DEFAULT,
 * FORMAT_LENGTH_FULL, FORMAT_LENGTH_LONG etc., of class  TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader.
 * The format length has a technical background in the CLDR repository, and specifies whether a different number
 * pattern should be used. In most cases leaving this DEFAULT would be the correct choice.
 *
 * *Example setting format length (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	'TYPO3\FLOW3\Property\TypeConverter\FloatConverter', 'formatLength', \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_FULL
 *  );
 *
 * @api
 * @FLOW3\Scope("singleton")
 */
class FloatConverter extends AbstractTypeConverter {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\I18n\Parser\NumberParser
	 */
	protected $numberParser;

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('float', 'integer', 'string');

	/**
	 * @var string
	 */
	protected $targetType = 'float';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType, by doing a typecast.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return float|\TYPO3\FLOW3\Error\Error
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($source === NULL || strlen($source) === 0) {
			return NULL;
		} elseif (is_string($source) && $configuration instanceof \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface) {
			$source = $this->parseUsingLocaleIfConfigured($source, $configuration);
			if ($source instanceof Error) {
				return $source;
			}
		}

		if (!is_numeric($source)) {
			return new Error('"%s" cannot be converted to a float value.' , 1332934124, array($source));
		}
		return (float)$source;
	}

	/**
	 * Tries to parse the input using the NumberParser.
	 *
	 * @param string $source
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return float|\TYPO3\FLOW3\Validation\Error Parsed float number or error
	 */
	protected function parseUsingLocaleIfConfigured($source, \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuration = $this->getConfigurationKeysAndValues($configuration, array('locale', 'strictMode', 'formatLength', 'formatType'));

		if ($configuration['locale'] === NULL) {
			return $source;
		} elseif ($configuration['locale'] === TRUE) {
			$locale = $this->localizationService->getConfiguration()->getCurrentLocale();
		} elseif (is_string($configuration['locale'])) {
			$locale = new \TYPO3\FLOW3\I18n\Locale($configuration['locale']);
		} elseif ($configuration['locale'] instanceof \TYPO3\FLOW3\I18n\Locale) {
			$locale = $configuration['locale'];
		}

		if (!($locale instanceof \TYPO3\FLOW3\I18n\Locale)) {
			$exceptionMessage = 'Determined locale is not of type "\TYPO3\FLOW3\I18n\Locale", but of type "' . (is_object($locale) ? get_class($locale) : gettype($locale)) . '".';
			throw new InvalidPropertyMappingConfigurationException($exceptionMessage, 1334837413);
		}

		if ($configuration['strictMode'] === NULL || $configuration['strictMode'] === TRUE) {
			$strictMode = TRUE;
		} else {
			$strictMode = FALSE;
		}

		if ($configuration['formatLength'] !== NULL) {
			$formatLength = $configuration['formatLength'];
			NumbersReader::validateFormatLength($formatLength);
		} else {
			$formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT;
		}

		if ($configuration['formatType'] !== NULL) {
			$formatType = $configuration['formatType'];
			\TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::validateFormatType($formatType);
		} else {
			$formatType = NumbersReader::FORMAT_TYPE_DECIMAL;
		}

		if ($formatType === NumbersReader::FORMAT_TYPE_PERCENT) {
			$return = $this->numberParser->parsePercentNumber($source, $locale, $formatLength, $strictMode);
			if ($return === FALSE) {
				$return = new Error('A valid percent number is expected.', 1334839253);
			}
		} else {
			$return = $this->numberParser->parseDecimalNumber($source, $locale, $formatLength, $strictMode);
			if ($return === FALSE) {
				$return = new Error('A valid decimal number is expected.', 1334839260);
			}
		}

		return $return;
	}

	/**
	 * Helper method to collect configuration for this class.
	 *
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @param array $configurationKeys
	 * @return array
	 */
	protected function getConfigurationKeysAndValues(\TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration, array $configurationKeys) {
		$keysAndValues = array();
		foreach ($configurationKeys as $configurationKey) {
			$keysAndValues[$configurationKey] = $configuration->getConfigurationValue('TYPO3\FLOW3\Property\TypeConverter\FloatConverter', $configurationKey);
		}
		return $keysAndValues;
	}
}
?>