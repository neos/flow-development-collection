<?php
namespace Neos\Flow\I18n\Parser;

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
use Neos\Flow\I18n\Utility;

/**
 * Parser for numbers.
 *
 * This parser does not support full syntax of number formats as defined in
 * CLDR. It uses parsed formats from NumbersReader class.
 *
 * @Flow\Scope("singleton")
 * @see NumbersReader
 * @api
 * @todo Currency support
 */
class NumberParser
{
    /**
     * Regex pattern for matching one or more digits.
     */
    const PATTERN_MATCH_DIGITS = '/^[0-9]+$/';

    /**
     * Regex pattern for matching all except digits. It's used for clearing
     * string in lenient mode.
     */
    const PATTERN_MATCH_NOT_DIGITS = '/[^0-9]+/';

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
     * Parses number given as a string using provided format.
     *
     * @param string $numberToParse Number to be parsed
     * @param string $format Number format to use
     * @param Locale $locale Locale to use
     * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
     * @return mixed Parsed float number or FALSE on failure
     * @api
     */
    public function parseNumberWithCustomPattern($numberToParse, $format, Locale $locale, $strictMode = true)
    {
        return $this->doParsingWithParsedFormat($numberToParse, $this->numbersReader->parseCustomFormat($format), $this->numbersReader->getLocalizedSymbolsForLocale($locale), $strictMode);
    }

    /**
     * Parses decimal number using proper format from CLDR.
     *
     * @param string $numberToParse Number to be parsed
     * @param Locale $locale Locale to use
     * @param string $formatLength One of NumbersReader FORMAT_LENGTH constants
     * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
     * @return mixed Parsed float number or FALSE on failure
     * @api
     */
    public function parseDecimalNumber($numberToParse, Locale $locale, $formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT, $strictMode = true)
    {
        NumbersReader::validateFormatLength($formatLength);
        return $this->doParsingWithParsedFormat($numberToParse, $this->numbersReader->parseFormatFromCldr($locale, NumbersReader::FORMAT_TYPE_DECIMAL, $formatLength), $this->numbersReader->getLocalizedSymbolsForLocale($locale), $strictMode);
    }

    /**
     * Parses percent number using proper format from CLDR.
     *
     * @param string $numberToParse Number to be parsed
     * @param Locale $locale Locale to use
     * @param string $formatLength One of NumbersReader FORMAT_LENGTH constants
     * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
     * @return mixed Parsed float number or FALSE on failure
     * @api
     */
    public function parsePercentNumber($numberToParse, Locale $locale, $formatLength = NumbersReader::FORMAT_LENGTH_DEFAULT, $strictMode = true)
    {
        NumbersReader::validateFormatLength($formatLength);
        return $this->doParsingWithParsedFormat($numberToParse, $this->numbersReader->parseFormatFromCldr($locale, NumbersReader::FORMAT_TYPE_PERCENT, $formatLength), $this->numbersReader->getLocalizedSymbolsForLocale($locale), $strictMode);
    }

    /**
     * Parses number using parsed format, in strict or lenient mode.
     *
     * @param string $numberToParse Number to be parsed
     * @param array $parsedFormat Parsed format (from NumbersReader)
     * @param array $localizedSymbols An array with symbols to use
     * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
     * @return mixed Parsed float number or FALSE on failure
     */
    protected function doParsingWithParsedFormat($numberToParse, array $parsedFormat, array $localizedSymbols, $strictMode)
    {
        return ($strictMode) ? $this->doParsingInStrictMode($numberToParse, $parsedFormat, $localizedSymbols) : $this->doParsingInLenientMode($numberToParse, $parsedFormat, $localizedSymbols);
    }

    /**
     * Parses number in strict mode.
     *
     * In strict mode parser checks all constraints of provided parsed format,
     * and if any of them is not fullfiled, parsing fails (FALSE is returned).
     *
     * @param string $numberToParse Number to be parsed
     * @param array $parsedFormat Parsed format (from NumbersReader)
     * @param array $localizedSymbols An array with symbols to use
     * @return mixed Parsed float number or FALSE on failure
     */
    protected function doParsingInStrictMode($numberToParse, array $parsedFormat, array $localizedSymbols)
    {
        $numberIsNegative = false;

        if (!empty($parsedFormat['negativePrefix']) && !empty($parsedFormat['negativeSuffix'])) {
            if (Utility::stringBeginsWith($numberToParse, $parsedFormat['negativePrefix']) && Utility::stringEndsWith($numberToParse, $parsedFormat['negativeSuffix'])) {
                $numberToParse = substr($numberToParse, strlen($parsedFormat['negativePrefix']), - strlen($parsedFormat['negativeSuffix']));
                $numberIsNegative = true;
            }
        } elseif (!empty($parsedFormat['negativePrefix']) && Utility::stringBeginsWith($numberToParse, $parsedFormat['negativePrefix'])) {
            $numberToParse = substr($numberToParse, strlen($parsedFormat['negativePrefix']));
            $numberIsNegative = true;
        } elseif (!empty($parsedFormat['negativeSuffix']) && Utility::stringEndsWith($numberToParse, $parsedFormat['negativeSuffix'])) {
            $numberToParse = substr($numberToParse, 0, - strlen($parsedFormat['negativeSuffix']));
            $numberIsNegative = true;
        }

        if (!$numberIsNegative) {
            if (!empty($parsedFormat['positivePrefix']) && !empty($parsedFormat['positiveSuffix'])) {
                if (Utility::stringBeginsWith($numberToParse, $parsedFormat['positivePrefix']) && Utility::stringEndsWith($numberToParse, $parsedFormat['positiveSuffix'])) {
                    $numberToParse = substr($numberToParse, strlen($parsedFormat['positivePrefix']), - strlen($parsedFormat['positiveSuffix']));
                } else {
                    return false;
                }
            } elseif (!empty($parsedFormat['positivePrefix'])) {
                if (Utility::stringBeginsWith($numberToParse, $parsedFormat['positivePrefix'])) {
                    $numberToParse = substr($numberToParse, strlen($parsedFormat['positivePrefix']));
                } else {
                    return false;
                }
            } elseif (!empty($parsedFormat['positiveSuffix'])) {
                if (Utility::stringEndsWith($numberToParse, $parsedFormat['positiveSuffix'])) {
                    $numberToParse = substr($numberToParse, 0, - strlen($parsedFormat['positiveSuffix']));
                } else {
                    return false;
                }
            }
        }

        $positionOfDecimalSeparator = strpos($numberToParse, $localizedSymbols['decimal']);
        if ($positionOfDecimalSeparator === false) {
            $numberToParse = str_replace($localizedSymbols['group'], '', $numberToParse);

            if (strlen($numberToParse) < $parsedFormat['minIntegerDigits']) {
                return false;
            } elseif (preg_match(self::PATTERN_MATCH_DIGITS, $numberToParse, $matches) !== 1) {
                return false;
            }

            $integerPart = $numberToParse;
            $decimalPart = false;
        } else {
            if ($positionOfDecimalSeparator === 0 && $positionOfDecimalSeparator === strlen($numberToParse) - 1) {
                return false;
            }

            $numberToParse = str_replace([$localizedSymbols['group'], $localizedSymbols['decimal']], ['', '.'], $numberToParse);

            $positionOfDecimalSeparator = strpos($numberToParse, '.');
            $integerPart = substr($numberToParse, 0, $positionOfDecimalSeparator);
            $decimalPart = substr($numberToParse, $positionOfDecimalSeparator + 1);
        }

        if (strlen($integerPart) < $parsedFormat['minIntegerDigits']) {
            return false;
        } elseif (preg_match(self::PATTERN_MATCH_DIGITS, $integerPart, $matches) !== 1) {
            return false;
        }

        $parsedNumber = (int)$integerPart;

        if ($decimalPart !== false) {
            $countOfDecimalDigits = strlen($decimalPart);
            if ($countOfDecimalDigits < $parsedFormat['minDecimalDigits'] || $countOfDecimalDigits > $parsedFormat['maxDecimalDigits']) {
                return false;
            } elseif (preg_match(self::PATTERN_MATCH_DIGITS, $decimalPart, $matches) !== 1) {
                return false;
            }

            $parsedNumber = (float)($integerPart . '.' . $decimalPart);
        }

        $parsedNumber /= $parsedFormat['multiplier'];

        if ($parsedFormat['rounding'] !== 0.0 && ($parsedNumber - (int)($parsedNumber / $parsedFormat['rounding']) * $parsedFormat['rounding']) !== 0.0) {
            return false;
        }

        if ($numberIsNegative) {
            $parsedNumber = 0 - $parsedNumber;
        }

        return $parsedNumber;
    }

    /**
     * Parses number in lenient mode.
     *
     * Lenient parsing ignores everything that can be ignored, and tries to
     * extract number from the string, even if it's not well formed.
     *
     * Implementation is simple but should work more often than strict parsing.
     *
     * Algorithm:
     * 1. Find first digit
     * 2. Find last digit
     * 3. Find decimal separator between first and last digit (if any)
     * 4. Remove non-digits from integer part
     * 5. Remove non-digits from decimal part (optional)
     * 6. Try to match negative prefix before first digit
     * 7. Try to match negative suffix after last digit
     *
     * @param string $numberToParse Number to be parsed
     * @param array $parsedFormat Parsed format (from NumbersReader)
     * @param array $localizedSymbols An array with symbols to use
     * @return mixed Parsed float number or FALSE on failure
     */
    protected function doParsingInLenientMode($numberToParse, array $parsedFormat, array $localizedSymbols)
    {
        $numberIsNegative = false;
        $positionOfFirstDigit = null;
        $positionOfLastDigit = null;

        $charactersOfNumberString = str_split($numberToParse);
        foreach ($charactersOfNumberString as $position => $character) {
            if (ord($character) >= 48 && ord($character) <= 57) {
                $positionOfFirstDigit = $position;
                break;
            }
        }

        if ($positionOfFirstDigit === null) {
            return false;
        }

        krsort($charactersOfNumberString);
        foreach ($charactersOfNumberString as $position => $character) {
            if (ord($character) >= 48 && ord($character) <= 57) {
                $positionOfLastDigit = $position;
                break;
            }
        }

        $positionOfDecimalSeparator = strrpos($numberToParse, $localizedSymbols['decimal'], $positionOfFirstDigit);
        if ($positionOfDecimalSeparator === false) {
            $integerPart = substr($numberToParse, $positionOfFirstDigit, $positionOfLastDigit - $positionOfFirstDigit + 1);
            $decimalPart = false;
        } else {
            $integerPart = substr($numberToParse, $positionOfFirstDigit, $positionOfDecimalSeparator - $positionOfFirstDigit);
            $decimalPart = substr($numberToParse, $positionOfDecimalSeparator + 1, $positionOfLastDigit - $positionOfDecimalSeparator);
        }

        $parsedNumber = (int)preg_replace(self::PATTERN_MATCH_NOT_DIGITS, '', $integerPart);

        if ($decimalPart !== false) {
            $decimalPart = (int)preg_replace(self::PATTERN_MATCH_NOT_DIGITS, '', $decimalPart);
            $parsedNumber = (float)($parsedNumber . '.' . $decimalPart);
        }

        $partBeforeNumber = substr($numberToParse, 0, $positionOfFirstDigit);
        $partAfterNumber = substr($numberToParse, - (strlen($numberToParse) - $positionOfLastDigit - 1));

        if (!empty($parsedFormat['negativePrefix']) && !empty($parsedFormat['negativeSuffix'])) {
            if (Utility::stringEndsWith($partBeforeNumber, $parsedFormat['negativePrefix']) && Utility::stringBeginsWith($partAfterNumber, $parsedFormat['negativeSuffix'])) {
                $numberIsNegative = true;
            }
        } elseif (!empty($parsedFormat['negativePrefix']) && Utility::stringEndsWith($partBeforeNumber, $parsedFormat['negativePrefix'])) {
            $numberIsNegative = true;
        } elseif (!empty($parsedFormat['negativeSuffix']) && Utility::stringBeginsWith($partAfterNumber, $parsedFormat['negativeSuffix'])) {
            $numberIsNegative = true;
        }

        $parsedNumber /= $parsedFormat['multiplier'];

        if ($numberIsNegative) {
            $parsedNumber = 0 - $parsedNumber;
        }

        return $parsedNumber;
    }
}
