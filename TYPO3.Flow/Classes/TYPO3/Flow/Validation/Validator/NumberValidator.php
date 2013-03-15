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

use TYPO3\Flow\Annotations as Flow;

/**
 * Validator for general numbers.
 *
 * @api
 */
class NumberValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'locale' => array(NULL, 'The locale to use for number parsing', 'string|Locale'),
		'strictMode' => array(TRUE, 'Use strict mode for number parsing', 'boolean'),
		'formatLength' => array(\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT, 'The format length, see NumbersReader::FORMAT_LENGTH_*', 'string'),
		'formatType' => array(\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, 'The format type, see NumbersReader::FORMAT_TYPE_*', 'string')
	);

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Parser\NumberParser
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
	protected function isValid($value) {
		if (!isset($this->options['locale'])) {
			$locale = $this->localizationService->getConfiguration()->getDefaultLocale();
		} elseif (is_string($this->options['locale'])) {
			$locale = new \TYPO3\Flow\I18n\Locale($this->options['locale']);
		} elseif ($this->options['locale'] instanceof \TYPO3\Flow\I18n\Locale) {
			$locale = $this->options['locale'];
		} else {
			$this->addError('The "locale" option can be only set to string identifier, or Locale object.', 1281286579);
			return;
		}

		$strictMode = $this->options['strictMode'];

		$formatLength = $this->options['formatLength'];
		\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::validateFormatLength($formatLength);

		$formatType = $this->options['formatType'];
		\TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::validateFormatType($formatType);

		if ($formatType === \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT) {
			if ($this->numberParser->parsePercentNumber($value, $locale, $formatLength, $strictMode) === FALSE) {
				$this->addError('A valid percent number is expected.', 1281452093);
			}
			return;
		} else {
			if ($this->numberParser->parseDecimalNumber($value, $locale, $formatLength, $strictMode) === FALSE) {
				$this->addError('A valid decimal number is expected.', 1281452094);
			}
		}
	}
}

?>