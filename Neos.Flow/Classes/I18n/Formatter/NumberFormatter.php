<?php
namespace Neos\Flow\I18n\Formatter;

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
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\I18n\Locale;

/**
 * Formatter for numbers.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class NumberFormatter implements FormatterInterface
{
    /**
     * @var NumbersReader
     */
    protected $numbersReader;

    /**
     * @param NumbersReader $numbersReader
     * @return void
     */
    public function injectNumbersReader(NumbersReader $numbersReader)
    {
        $this->numbersReader = $numbersReader;
    }

    /**
     * Formats provided value using optional style properties
     *
     * @param mixed $value Formatter-specific variable to format (can be integer, \DateTime, etc)
     * @param Locale $locale Locale to use
     * @param array $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
     * @return string String representation of $value provided, or (string)$value
     * @api
     */
    public function format($value, Locale $locale, array $styleProperties = [])
    {
        if (isset($styleProperties[0])) {
            $formatType = $styleProperties[0];
            NumbersReader::validateFormatType($formatType);
        } else {
            $formatType = NumbersReader::FORMAT_TYPE_DECIMAL;
        }

        switch ($formatType) {
            case NumbersReader::FORMAT_TYPE_PERCENT:
                return $this->formatPercentNumber($value, $locale, NumbersReader::FORMAT_LENGTH_DEFAULT);
            default:
                return $this->formatDecimalNumber($value, $locale, NumbersReader::FORMAT_LENGTH_DEFAULT);
        }
    }

    /**
     * Returns number formatted by custom format, string provided in parameter.
     *
     * Format must obey syntax defined in CLDR specification, excluding
     * unimplemented features (see documentation for this class).
     *
     * Format is remembered in this classes cache and won't be parsed again for
     * some time.
     *
     * @param mixed $number Float or int, can be negative, can be NaN or infinite
     * @param string $format Format string
     * @param Locale $locale A locale used for finding symbols array
     * @return string Formatted number. Will return string-casted version of $number if pattern is not valid / supported
     * @api
     */
    public function formatNumberWithCustomPattern($number, $format, Locale $locale)
    {
        return $this->doFormattingWithParsedFormat($number, $this->numbersReader->parseCustomFormat($format), $this->numbersReader->getLocalizedSymbolsForLocale($locale));
    }

    /**
     * Formats number with format string for decimal numbers defined in CLDR for
     * particular locale.
     *
     * Note: currently length is not used in decimalFormats from CLDR.
     * But it's defined in the specification, so we support it here.
     *
     * @param mixed $number Float or int, can be negative, can be NaN or infinite
     * @param Locale $locale
     * @param string $formatLength One of NumbersReader FORMAT_LENGTH constants
     * @return string Formatted number. Will return string-casted version of $number if there is no pattern for given $locale / $formatLength
     * @api
     */
    public function formatDecimalNumber($number, Locale $locale, $formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT)
    {
        NumbersReader::validateFormatLength($formatLength);
        return $this->doFormattingWithParsedFormat($number, $this->numbersReader->parseFormatFromCldr($locale, NumbersReader::FORMAT_TYPE_DECIMAL, $formatLength), $this->numbersReader->getLocalizedSymbolsForLocale($locale));
    }

    /**
     * Formats number with format string for percentage defined in CLDR for
     * particular locale.
     *
     * Note: currently length is not used in percentFormats from CLDR.
     * But it's defined in the specification, so we support it here.
     *
     * @param mixed $number Float or int, can be negative, can be NaN or infinite
     * @param Locale $locale
     * @param string $formatLength One of NumbersReader FORMAT_LENGTH constants
     * @return string Formatted number. Will return string-casted version of $number if there is no pattern for given $locale / $formatLength
     * @api
     */
    public function formatPercentNumber($number, Locale $locale, $formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT)
    {
        NumbersReader::validateFormatLength($formatLength);
        return $this->doFormattingWithParsedFormat($number, $this->numbersReader->parseFormatFromCldr($locale, NumbersReader::FORMAT_TYPE_PERCENT, $formatLength), $this->numbersReader->getLocalizedSymbolsForLocale($locale));
    }

    /**
     * Formats number with format string for currency defined in CLDR for
     * particular locale.
     *
     * Currency symbol provided will be inserted into formatted number string.
     *
     * Note: currently length is not used in currencyFormats from CLDR.
     * But it's defined in the specification, so we support it here.
     *
     * @param mixed $number Float or int, can be negative, can be NaN or infinite
     * @param Locale $locale
     * @param string $currency Currency symbol (or name)
     * @param string $formatLength One of NumbersReader FORMAT_LENGTH constants
     * @return string Formatted number. Will return string-casted version of $number if there is no pattern for given $locale / $formatLength
     * @api
     */
    public function formatCurrencyNumber($number, Locale $locale, $currency, $formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT)
    {
        NumbersReader::validateFormatLength($formatLength);
        return $this->doFormattingWithParsedFormat($number, $this->numbersReader->parseFormatFromCldr($locale, NumbersReader::FORMAT_TYPE_CURRENCY, $formatLength), $this->numbersReader->getLocalizedSymbolsForLocale($locale), $currency);
    }

    /**
     * Formats provided float or integer.
     *
     * Format rules defined in $parsedFormat array are used. Localizable symbols
     * are replaced with elements from $symbols array, and currency
     * placeholder is replaced with the value of $currency, if not NULL.
     *
     * If $number is NaN or infinite, proper localized symbol will be returned,
     * as defined in CLDR specification.
     *
     * @param mixed $number Float or int, can be negative, can be NaN or infinite
     * @param array $parsedFormat An array describing format (as in $parsedFormats property)
     * @param array $symbols An array with symbols to use (as in $localeSymbols property)
     * @param string $currency Currency symbol to be inserted into formatted number (if applicable)
     * @return string Formatted number. Will return string-casted version of $number if pattern is FALSE
     */
    protected function doFormattingWithParsedFormat($number, array $parsedFormat, array $symbols, $currency = null)
    {
        if ($parsedFormat === false) {
            return (string)$number;
        }

        if (is_nan($number)) {
            return $symbols['nan'];
        }

        if (is_infinite($number)) {
            if ($number < 0) {
                return $parsedFormat['negativePrefix'] . $symbols['infinity'] . $parsedFormat['negativeSuffix'];
            } else {
                return $parsedFormat['positivePrefix'] . $symbols['infinity'] . $parsedFormat['positiveSuffix'];
            }
        }

        $isNegative = $number < 0;
        $number = abs($number * $parsedFormat['multiplier']);

        if ($parsedFormat['rounding'] > 0) {
            $number = round($number / $parsedFormat['rounding'], 0, \PHP_ROUND_HALF_EVEN) * $parsedFormat['rounding'];
        }

        if ($parsedFormat['maxDecimalDigits'] >= 0) {
            $number = round($number, $parsedFormat['maxDecimalDigits']);
        }

        $number = (string)$number;

        if (($positionOfDecimalSeparator = strpos($number, '.')) !== false) {
            $integerPart = substr($number, 0, $positionOfDecimalSeparator);
            $decimalPart = substr($number, $positionOfDecimalSeparator + 1);
        } else {
            $integerPart = $number;
            $decimalPart = '';
        }

        if ($parsedFormat['minDecimalDigits'] > strlen($decimalPart)) {
            $decimalPart = str_pad($decimalPart, $parsedFormat['minDecimalDigits'], '0');
        }

        $integerPart = str_pad($integerPart, $parsedFormat['minIntegerDigits'], '0', STR_PAD_LEFT);

        if ($parsedFormat['primaryGroupingSize'] > 0 && strlen($integerPart) > $parsedFormat['primaryGroupingSize']) {
            $primaryGroupOfIntegerPart = substr($integerPart, - $parsedFormat['primaryGroupingSize']);
            $restOfIntegerPart = substr($integerPart, 0, - $parsedFormat['primaryGroupingSize']);

            // Pad the numbers with spaces from the left, so the length of the string is a multiply of secondaryGroupingSize (and str_split() can split on equal parts)
            $padLengthToGetEvenSize = (int)((strlen($restOfIntegerPart) + $parsedFormat['secondaryGroupingSize'] - 1) / $parsedFormat['secondaryGroupingSize']) * $parsedFormat['secondaryGroupingSize'];
            $restOfIntegerPart = str_pad($restOfIntegerPart, $padLengthToGetEvenSize, ' ', STR_PAD_LEFT);

            // Insert localized group separators between every secondary groups and primary group (using str_split() and implode())
            $secondaryGroupsOfIntegerPart = str_split($restOfIntegerPart, $parsedFormat['secondaryGroupingSize']);
            $integerPart = ltrim(implode($symbols['group'], $secondaryGroupsOfIntegerPart)) . $symbols['group'] . $primaryGroupOfIntegerPart;
        }

        if (strlen($decimalPart) > 0) {
            $decimalPart = $symbols['decimal'] . $decimalPart;
        }

        if ($isNegative) {
            $number = $parsedFormat['negativePrefix'] . $integerPart . $decimalPart . $parsedFormat['negativeSuffix'];
        } else {
            $number = $parsedFormat['positivePrefix'] . $integerPart . $decimalPart . $parsedFormat['positiveSuffix'];
        }

        $number = str_replace(['%', '‰', '-'], [$symbols['percentSign'], $symbols['perMille'], $symbols['minusSign']], $number);
        if ($currency !== null) {
            // @todo When currency is set, min / max DecimalDigits and rounding is overridden with CLDR data
            $number = str_replace('¤', $currency, $number);
        }

        return $number;
    }
}
