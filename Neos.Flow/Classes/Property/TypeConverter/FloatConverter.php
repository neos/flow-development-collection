<?php
namespace Neos\Flow\Property\TypeConverter;

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
use Neos\Error\Messages\Error;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 * Converter which transforms a float, integer or string to a float.
 *
 * This is basically done by simply casting it, unless the input is a string and you provide some configuration
 * options which will make this converter use Flow's locale parsing capabilities in order to respect deviating
 * decimal separators.
 *
 * Using NULL or an empty string as input will result in a NULL return value.
 *
 * **Advanced usage in action controller context**
 *
 * *Using default locale*::
 *
 *  protected function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		\Neos\Flow\Property\TypeConverter\FloatConverter::class, 'locale', TRUE
 *  	);
 *  }
 *
 * Just providing TRUE as option value will use the current default locale. In case that default locale is "DE"
 * for Germany for example, where a comma is used as decimal separator, the mentioned code will return
 * (float)15.5 when the input was (string)"15,50".
 *
 * *Using arbitrary locale*::
 *
 *  protected function initializeCreateAction() {
 *  	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 *  		\Neos\Flow\Property\TypeConverter\FloatConverter::class, 'locale', 'fr'
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
 *  	\Neos\Flow\Property\TypeConverter\FloatConverter::class, 'strictMode', FALSE
 *  );
 *
 * **Format type**
 *
 * Format type can be decimal, percent or currency; represented as class constant FORMAT_TYPE_DECIMAL,
 * FORMAT_TYPE_PERCENT or FORMAT_TYPE_CURRENCY of class Neos\Flow\I18n\Cldr\Reader\NumbersReader.
 * Default, if none given, is FORMAT_TYPE_DECIMAL.
 *
 * *Example setting format type `currency` (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	\Neos\Flow\Property\TypeConverter\FloatConverter::class, 'formatType', \Neos\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_CURRENCY
 *  );
 *
 * **Format length**
 *
 * Format type can be default, full, long, medium or short; represented as class constant FORMAT_LENGTH_DEFAULT,
 * FORMAT_LENGTH_FULL, FORMAT_LENGTH_LONG etc., of class  Neos\Flow\I18n\Cldr\Reader\NumbersReader.
 * The format length has a technical background in the CLDR repository, and specifies whether a different number
 * pattern should be used. In most cases leaving this DEFAULT would be the correct choice.
 *
 * *Example setting format length (abridged)*::
 *
 *  ->setTypeConverterOption(
 *  	\Neos\Flow\Property\TypeConverter\FloatConverter::class, 'formatLength', \Neos\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_FULL
 *  );
 *
 * @api
 * @Flow\Scope("singleton")
 */
class FloatConverter extends AbstractTypeConverter
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $localizationService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Parser\NumberParser
     */
    protected $numberParser;

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['float', 'integer', 'string'];

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
     * @param PropertyMappingConfigurationInterface $configuration
     * @return float|\Neos\Error\Messages\Error
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($source === null || $source === '') {
            return null;
        } elseif (is_string($source) && $configuration instanceof PropertyMappingConfigurationInterface) {
            $source = $this->parseUsingLocaleIfConfigured($source, $configuration);
            if ($source instanceof Error) {
                return $source;
            }
        }

        if (!is_numeric($source)) {
            return new Error('"%s" cannot be converted to a float value.', 1332934124, [$source]);
        }
        return (float)$source;
    }

    /**
     * Tries to parse the input using the NumberParser.
     *
     * @param string $source
     * @param PropertyMappingConfigurationInterface $configuration
     * @return float|\Neos\Flow\Validation\Error Parsed float number or error
     * @throws \Neos\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    protected function parseUsingLocaleIfConfigured($source, PropertyMappingConfigurationInterface $configuration)
    {
        $configuration = $this->getConfigurationKeysAndValues($configuration, ['locale', 'strictMode', 'formatLength', 'formatType']);

        if ($configuration['locale'] === null) {
            return $source;
        } elseif ($configuration['locale'] === true) {
            $locale = $this->localizationService->getConfiguration()->getCurrentLocale();
        } elseif (is_string($configuration['locale'])) {
            $locale = new Locale($configuration['locale']);
        } elseif ($configuration['locale'] instanceof Locale) {
            $locale = $configuration['locale'];
        }

        if (!($locale instanceof Locale)) {
            $exceptionMessage = 'Determined locale is not of type "\Neos\Flow\I18n\Locale", but of type "' . (is_object($locale) ? get_class($locale) : gettype($locale)) . '".';
            throw new InvalidPropertyMappingConfigurationException($exceptionMessage, 1334837413);
        }

        if ($configuration['strictMode'] === null || $configuration['strictMode'] === true) {
            $strictMode = true;
        } else {
            $strictMode = false;
        }

        if ($configuration['formatLength'] !== null) {
            $formatLength = $configuration['formatLength'];
            NumbersReader::validateFormatLength($formatLength);
        } else {
            $formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT;
        }

        if ($configuration['formatType'] !== null) {
            $formatType = $configuration['formatType'];
            NumbersReader::validateFormatType($formatType);
        } else {
            $formatType = NumbersReader::FORMAT_TYPE_DECIMAL;
        }

        if ($formatType === NumbersReader::FORMAT_TYPE_PERCENT) {
            $return = $this->numberParser->parsePercentNumber($source, $locale, $formatLength, $strictMode);
            if ($return === false) {
                $return = new Error('A valid percent number is expected.', 1334839253);
            }
        } else {
            $return = $this->numberParser->parseDecimalNumber($source, $locale, $formatLength, $strictMode);
            if ($return === false) {
                $return = new Error('A valid decimal number is expected.', 1334839260);
            }
        }

        return $return;
    }

    /**
     * Helper method to collect configuration for this class.
     *
     * @param PropertyMappingConfigurationInterface $configuration
     * @param array $configurationKeys
     * @return array
     */
    protected function getConfigurationKeysAndValues(PropertyMappingConfigurationInterface $configuration, array $configurationKeys)
    {
        $keysAndValues = [];
        foreach ($configurationKeys as $configurationKey) {
            $keysAndValues[$configurationKey] = $configuration->getConfigurationValue(FloatConverter::class, $configurationKey);
        }
        return $keysAndValues;
    }
}
