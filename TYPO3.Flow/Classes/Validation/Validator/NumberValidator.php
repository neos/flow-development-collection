<?php
namespace TYPO3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Validator for general numbers
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class NumberValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * @var \TYPO3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @var \TYPO3\FLOW3\I18n\Parser\NumberParser
	 */
	protected $numberParser;

	/**
	 * @param \TYPO3\FLOW3\I18n\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\TYPO3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\Parser\NumberParser $numberParser
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectNumberParser(\TYPO3\FLOW3\I18n\Parser\NumberParser $numberParser) {
		$this->numberParser = $numberParser;
	}

	/**
	 * Checks if the given value is a valid number.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 * @todo Currency support should be added when it will be supported by NumberParser
	 */
	protected function isValid($value) {
		if (!isset($this->options['locale'])) {
			$locale = $this->localizationService->getDefaultLocale();
		} elseif (is_string($this->options['locale'])) {
			$locale = new \TYPO3\FLOW3\I18n\Locale($this->options['locale']);
		} elseif ($this->options['locale'] instanceof \TYPO3\FLOW3\I18n\Locale) {
			$locale = $this->options['locale'];
		} else {
			$this->addError('The "locale" option can be only set to string identifier, or Locale object.', 1281286579);
			return;
		}

		if (!isset($this->options['strictMode']) || $this->options['strictMode'] === TRUE) {
			$strictMode = TRUE;
		} else {
			$strictMode = FALSE;
		}

		if (isset($this->options['formatLength'])) {
			$formatLength = $this->options['formatLength'];
			\TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::validateFormatLength($formatLength);
		} else {
			$formatLength = \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT;
		}

		if (isset($this->options['formatType'])) {
			$formatType = $this->options['formatType'];
			\TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::validateFormatType($formatType);
		} else {
			$formatType = \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL;
		}

		if ($formatType === \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT) {
			if ($this->numberParser->parsePercentNumber($value, $locale, $formatLength, $strictMode) === FALSE) {
				$this->addError('A valid percent number is expected.', 1281452093);
			} else {
				return;
			}
		} else {
			if ($this->numberParser->parseDecimalNumber($value, $locale, $formatLength, $strictMode) === FALSE) {
				$this->addError('A valid decimal number is expected.', 1281452094);
			} else {
				return;
			}
		}
	}
}

?>