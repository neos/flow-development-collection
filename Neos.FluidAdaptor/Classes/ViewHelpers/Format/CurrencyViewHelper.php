<?php
namespace Neos\FluidAdaptor\ViewHelpers\Format;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception as I18nException;
use Neos\Flow\I18n\Formatter\NumberFormatter;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractLocaleAwareViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;

/**
 * Formats a given float to a currency representation.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.currency>123.456</f:format.currency>
 * </code>
 * <output>
 * 123,46
 * </output>
 *
 * <code title="All parameters">
 * <f:format.currency currencySign="$" decimalSeparator="." thousandsSeparator="," prependCurrency="false", separateCurrency="true", decimals="2">54321</f:format.currency>
 * </code>
 * <output>
 * 54,321.00 $
 * </output>
 *
 * <code title="Inline notation">
 * {someNumber -> f:format.currency(thousandsSeparator: ',', currencySign: '€')}
 * </code>
 * <output>
 * 54,321,00 €
 * (depending on the value of {someNumber})
 * </output>
 *
 * <code title="Inline notation with current locale used">
 * {someNumber -> f:format.currency(currencySign: '€', forceLocale: true)}
 * </code>
 * <output>
 * 54.321,00 €
 * (depending on the value of {someNumber} and the current locale)
 * </output>
 *
 * <code title="Inline notation with specific locale used">
 * {someNumber -> f:format.currency(currencySign: 'EUR', forceLocale: 'de_DE')}
 * </code>
 * <output>
 * 54.321,00 EUR
 * (depending on the value of {someNumber})
 * </output>
 *
 * <code title="Inline notation with different position for the currency sign">
 * {someNumber -> f:format.currency(currencySign: '€', prependCurrency: 'true')}
 * </code>
 * <output>
 * € 54.321,00
 * (depending on the value of {someNumber})
 * </output>
 *
 * <code title="Inline notation with no space between the currency and no decimal places">
 * {someNumber -> f:format.currency(currencySign: '€', separateCurrency: 'false', decimals: '0')}
 * </code>
 * <output>
 * 54.321€
 * (depending on the value of {someNumber})
 * </output>
 *
 * Note: This ViewHelper is intended to help you with formatting numbers into monetary units.
 * Complex calculations and/or conversions should be done before the number is passed.
 *
 * Also be aware that if the ``locale`` is set, all arguments except for the currency sign (which
 * then becomes mandatory) are ignored and the CLDR (Common Locale Data Repository) is used for formatting.
 * Fore more information about localization see section ``Internationalization & Localization Framework`` in the
 * Flow documentation.
 *
 * @api
 */
class CurrencyViewHelper extends AbstractLocaleAwareViewHelper
{
    /**
     * @Flow\Inject
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @param string $currencySign (optional) The currency sign, eg $ or €.
     * @param string $decimalSeparator (optional) The separator for the decimal point.
     * @param string $thousandsSeparator (optional) The thousands separator.
     * @param boolean $prependCurrency (optional) Indicates if currency symbol should be placed before or after the numeric value.
     * @param boolean $separateCurrency (optional) Indicates if a space character should be placed between the number and the currency sign.
     * @param integer $decimals (optional) The number of decimal places.
     *
     * @throws InvalidVariableException
     * @return string the formatted amount.
     * @throws ViewHelperException
     * @api
     */
    public function render($currencySign = '', $decimalSeparator = ',', $thousandsSeparator = '.', $prependCurrency = false, $separateCurrency = true, $decimals = 2)
    {
        $stringToFormat = $this->renderChildren();

        $useLocale = $this->getLocale();
        if ($useLocale !== null) {
            if ($currencySign === '') {
                throw new InvalidVariableException('Using the Locale requires a currencySign.', 1326378320);
            }
            try {
                $output = $this->numberFormatter->formatCurrencyNumber($stringToFormat, $useLocale, $currencySign);
            } catch (I18nException $exception) {
                throw new ViewHelperException($exception->getMessage(), 1382350428, $exception);
            }

            return $output;
        }

        $output = number_format((float)$stringToFormat, $decimals, $decimalSeparator, $thousandsSeparator);
        if (empty($currencySign)) {
            return $output;
        }
        if ($prependCurrency === true) {
            $output = $currencySign . ($separateCurrency === true ? ' ' : '') . $output;

            return $output;
        }
        $output .= ($separateCurrency === true ? ' ' : '') . $currencySign;

        return $output;
    }
}
