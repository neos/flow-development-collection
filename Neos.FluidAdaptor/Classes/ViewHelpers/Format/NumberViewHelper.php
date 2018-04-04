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
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;

/**
 * Formats a number with custom precision, decimal point and grouped thousands.
 * @see http://www.php.net/manual/en/function.number-format.php
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.number>423423.234</f:format.number>
 * </code>
 * <output>
 * 423,423.20
 * </output>
 *
 * <code title="With all parameters">
 * <f:format.number decimals="1" decimalSeparator="," thousandsSeparator=".">423423.234</f:format.number>
 * </code>
 * <output>
 * 423.423,2
 * </output>
 *
 * <code title="Inline notation with current locale used">
 * {someNumber -> f:format.number(forceLocale: true)}
 * </code>
 * <output>
 * 54.321,00
 * (depending on the value of {someNumber} and the current locale)
 * </output>
 *
 * <code title="Inline notation with specific locale used">
 * {someNumber -> f:format.currency(forceLocale: 'de_DE')}
 * </code>
 * <output>
 * 54.321,00
 * (depending on the value of {someNumber})
 * </output>
 *
 * @api
 */
class NumberViewHelper extends AbstractLocaleAwareViewHelper
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
        $this->registerArgument('decimals', 'integer', 'The number of digits after the decimal point', false, 2);
        $this->registerArgument('decimalSeparator', 'string', 'The decimal point character', false, '.');
        $this->registerArgument('thousandsSeparator', 'string', 'The character for grouping the thousand digits', false, ',');
        $this->registerArgument('localeFormatLength', 'string', 'Format length if locale set in $forceLocale. Must be one of Neos\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_*\'s constants.', false, NumbersReader::FORMAT_LENGTH_DEFAULT);
    }

    /**
     * Format the numeric value as a number with grouped thousands, decimal point and
     * precision.
     *
     * @return string The formatted number
     * @api
     * @throws ViewHelperException
     */
    public function render()
    {
        $stringToFormat = $this->renderChildren();

        $useLocale = $this->getLocale();
        if ($useLocale !== null) {
            try {
                $output = $this->numberFormatter->formatDecimalNumber($stringToFormat, $useLocale, $this->arguments['localeFormatLength']);
            } catch (I18nException $exception) {
                throw new ViewHelperException($exception->getMessage(), 1382351148, $exception);
            }
        } else {
            $output = number_format((float)$stringToFormat, $this->arguments['decimals'], $this->arguments['decimalSeparator'], $this->arguments['thousandsSeparator']);
        }
        return $output;
    }
}
