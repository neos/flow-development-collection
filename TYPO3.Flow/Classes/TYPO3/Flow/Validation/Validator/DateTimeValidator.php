<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Validator for DateTime objects.
 *
 * @api
 */
class DateTimeValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'locale' => array(NULL, 'The locale to use for date parsing', 'string|Locale'),
		'strictMode' => array(TRUE, 'Use strict mode for date parsing', 'boolean'),
		'formatLength' => array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, 'The format length, see DatesReader::FORMAT_LENGTH_*', 'string'),
		'formatType' => array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, 'The format type, see DatesReader::FORMAT_TYPE_*', 'string')
	);

	/**
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @var \TYPO3\Flow\I18n\Parser\DatetimeParser
	 */
	protected $datetimeParser;

	/**
	 * @param \TYPO3\Flow\I18n\Service $localizationService
	 * @return void
	 */
	public function injectLocalizationService(\TYPO3\Flow\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\Flow\I18n\Parser\DatetimeParser $datetimeParser
	 * @return void
	 */
	public function injectDatetimeParser(\TYPO3\Flow\I18n\Parser\DatetimeParser $datetimeParser) {
		$this->datetimeParser = $datetimeParser;
	}

	/**
	 * Checks if the given value is a valid DateTime object.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if ($value instanceof \DateTime) {
			return;
		}
		if (!isset($this->options['locale'])) {
			$locale = $this->localizationService->getConfiguration()->getDefaultLocale();
		} elseif (is_string($this->options['locale'])) {
			$locale = new \TYPO3\Flow\I18n\Locale($this->options['locale']);
		} elseif ($this->options['locale'] instanceof \TYPO3\Flow\I18n\Locale) {
			$locale = $this->options['locale'];
		} else {
			$this->addError('The "locale" option can be only set to string identifier, or Locale object.', 1281454676);
			return;
		}

		$strictMode = $this->options['strictMode'];

		$formatLength = $this->options['formatLength'];
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);

		$formatType = $this->options['formatType'];
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatType($formatType);

		if ($formatType === \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME) {
			if ($this->datetimeParser->parseTime($value, $locale, $formatLength, $strictMode) === FALSE) {
				$this->addError('A valid time is expected.', 1281454830);
			}
		} elseif ($formatType === \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME) {
			if ($this->datetimeParser->parseDateAndTime($value, $locale, $formatLength, $strictMode) === FALSE) {
				$this->addError('A valid date and time is expected.', 1281454831);
			}
		} else {
			if ($this->datetimeParser->parseDate($value, $locale, $formatLength, $strictMode) === FALSE) {
				$this->addError('A valid date is expected.', 1281454832);
			}
		}
	}
}

?>