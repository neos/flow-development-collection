<?php
namespace Neos\Flow\Validation\Validator;

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
use Neos\Flow\I18n;

/**
 * Validator for general numbers.
 *
 * @api
 */
class NumberValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'locale' => [null, 'The locale to use for number parsing', 'string|Locale'],
        'strictMode' => [true, 'Use strict mode for number parsing', 'boolean'],
        'formatLength' => [NumbersReader::FORMAT_LENGTH_DEFAULT, 'The format length, see NumbersReader::FORMAT_LENGTH_*', 'string'],
        'formatType' => [NumbersReader::FORMAT_TYPE_DECIMAL, 'The format type, see NumbersReader::FORMAT_TYPE_*', 'string']
    ];

    /**
     * @Flow\Inject
     * @var I18n\Service
     */
    protected $localizationService;

    /**
     * @Flow\Inject
     * @var I18n\Parser\NumberParser
     */
    protected $numberParser;

    /**
     * Checks if the given value is a valid number.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     * @todo Currency support should be added when it will be supported by NumberParser
     */
    protected function isValid($value)
    {
        if (!isset($this->options['locale'])) {
            $locale = $this->localizationService->getConfiguration()->getDefaultLocale();
        } elseif (is_string($this->options['locale'])) {
            $locale = new I18n\Locale($this->options['locale']);
        } elseif ($this->options['locale'] instanceof I18n\Locale) {
            $locale = $this->options['locale'];
        } else {
            $this->addError('The "locale" option can be only set to string identifier, or Locale object.', 1281286579);
            return;
        }

        $strictMode = $this->options['strictMode'];

        $formatLength = $this->options['formatLength'];
        NumbersReader::validateFormatLength($formatLength);

        $formatType = $this->options['formatType'];
        NumbersReader::validateFormatType($formatType);

        if ($formatType === NumbersReader::FORMAT_TYPE_PERCENT) {
            if ($this->numberParser->parsePercentNumber($value, $locale, $formatLength, $strictMode) === false) {
                $this->addError('A valid percent number is expected.', 1281452093);
            }
            return;
        } else {
            if ($this->numberParser->parseDecimalNumber($value, $locale, $formatLength, $strictMode) === false) {
                $this->addError('A valid decimal number is expected.', 1281452094);
            }
        }
    }
}
