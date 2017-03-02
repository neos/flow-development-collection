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
use Neos\Flow\I18n\Cldr\Reader\DatesReader;
use Neos\Flow\I18n;

/**
 * Validator for DateTime objects.
 *
 * @api
 */
class DateTimeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'locale' => [null, 'The locale to use for date parsing', 'string|Locale'],
        'strictMode' => [true, 'Use strict mode for date parsing', 'boolean'],
        'formatLength' => [DatesReader::FORMAT_LENGTH_DEFAULT, 'The format length, see DatesReader::FORMAT_LENGTH_*', 'string'],
        'formatType' => [DatesReader::FORMAT_TYPE_DATE, 'The format type, see DatesReader::FORMAT_TYPE_*', 'string']
    ];

    /**
     * @Flow\Inject
     * @var I18n\Service
     */
    protected $localizationService;

    /**
     * @Flow\Inject
     * @var I18n\Parser\DatetimeParser
     */
    protected $datetimeParser;

    /**
     * Checks if the given value is a valid DateTime object.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return;
        }
        if (!isset($this->options['locale'])) {
            $locale = $this->localizationService->getConfiguration()->getDefaultLocale();
        } elseif (is_string($this->options['locale'])) {
            $locale = new I18n\Locale($this->options['locale']);
        } elseif ($this->options['locale'] instanceof I18n\Locale) {
            $locale = $this->options['locale'];
        } else {
            $this->addError('The "locale" option can be only set to string identifier, or Locale object.', 1281454676);
            return;
        }

        $strictMode = $this->options['strictMode'];

        $formatLength = $this->options['formatLength'];
        DatesReader::validateFormatLength($formatLength);

        $formatType = $this->options['formatType'];
        DatesReader::validateFormatType($formatType);

        if ($formatType === DatesReader::FORMAT_TYPE_TIME) {
            if ($this->datetimeParser->parseTime($value, $locale, $formatLength, $strictMode) === false) {
                $this->addError('A valid time is expected.', 1281454830);
            }
        } elseif ($formatType === DatesReader::FORMAT_TYPE_DATETIME) {
            if ($this->datetimeParser->parseDateAndTime($value, $locale, $formatLength, $strictMode) === false) {
                $this->addError('A valid date and time is expected.', 1281454831);
            }
        } else {
            if ($this->datetimeParser->parseDate($value, $locale, $formatLength, $strictMode) === false) {
                $this->addError('A valid date is expected.', 1281454832);
            }
        }
    }
}
