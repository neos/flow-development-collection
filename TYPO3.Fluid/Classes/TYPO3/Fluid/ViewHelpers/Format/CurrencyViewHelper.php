<?php
namespace TYPO3\Fluid\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Exception as I18nException;
use TYPO3\Flow\I18n\Formatter\NumberFormatter;
use TYPO3\Fluid\Core\ViewHelper\AbstractLocaleAwareViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;

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
 * <f:format.currency currencySign="$" decimalSeparator="." thousandsSeparator=",">54321</f:format.currency>
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
     *
     * @throws InvalidVariableException
     * @return string the formatted amount.
     * @throws ViewHelperException
     * @api
     */
    public function render($currencySign = '', $decimalSeparator = ',', $thousandsSeparator = '.')
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
        } else {
            $output = number_format((float)$stringToFormat, 2, $decimalSeparator, $thousandsSeparator);
            if ($currencySign !== '') {
                $output .= ' ' . $currencySign;
            }
        }
        return $output;
    }
}
