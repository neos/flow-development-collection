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
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
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
 * Additionally, if ``currencyCode`` is set, rounding and decimal digits are replaced by the rules for the
 * respective currency (e.g. JPY never has decimal digits, CHF is rounded using 5 decimals.)
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
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('currencySign', 'string', '(optional) The currency sign, eg $ or €.', false, '');
        $this->registerArgument('decimalSeparator', 'string', '(optional) The separator for the decimal point.', false, ',');
        $this->registerArgument('thousandsSeparator', 'string', '(optional) The thousands separator.', false, '.');
        $this->registerArgument('prependCurrency', 'boolean', '(optional) Indicates if currency symbol should be placed before or after the numeric value.', false, false);
        $this->registerArgument('separateCurrency', 'boolean', '(optional) Indicates if a space character should be placed between the number and the currency sign.', false, true);
        $this->registerArgument('decimals', 'integer', '(optional) The number of decimal places.', false, 2);
        $this->registerArgument('currencyCode', 'string', '(optional) The ISO 4217 currency code of the currency to format. Used to set decimal places and rounding.', false, null);
    }

    /**
     *
     * @throws InvalidVariableException
     * @return string the formatted amount.
     * @throws ViewHelperException
     * @api
     */
    public function render()
    {
        $stringToFormat = $this->renderChildren();
        $currencySign = $this->arguments['currencySign'];
        $separateCurrency = $this->arguments['separateCurrency'];

        $useLocale = $this->getLocale();
        if ($useLocale !== null) {
            if ($currencySign === '') {
                throw new InvalidVariableException('Using the Locale requires a currencySign.', 1326378320);
            }
            try {
                $output = $this->numberFormatter->formatCurrencyNumber($stringToFormat, $useLocale, $currencySign, NumbersReader::FORMAT_LENGTH_DEFAULT, $this->arguments['currencyCode']);
            } catch (I18nException $exception) {
                throw new ViewHelperException($exception->getMessage(), 1382350428, $exception);
            }

            return $output;
        }

        $output = number_format((float)$stringToFormat, $this->arguments['decimals'], $this->arguments['decimalSeparator'], $this->arguments['thousandsSeparator']);
        if (empty($currencySign)) {
            return $output;
        }
        if ($this->arguments['prependCurrency'] === true) {
            $output = $currencySign . ($separateCurrency === true ? ' ' : '') . $output;

            return $output;
        }
        $output .= ($separateCurrency === true ? ' ' : '') . $currencySign;

        return $output;
    }
}
