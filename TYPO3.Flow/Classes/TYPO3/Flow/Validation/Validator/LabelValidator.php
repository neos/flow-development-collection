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
 * A validator for labels.
 *
 * Labels usually allow all kinds of letters, numbers, punctuation marks and
 * the space character. What you don't want in labels though are tabs, new
 * line characters or HTML tags. This validator is for such uses.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class LabelValidator extends AbstractValidator {

	const PATTERN_VALIDCHARACTERS = '/^[\p{L}\p{Sc} ,.:;?!%§&"\'\/+\-_=\(\)#0-9]*$/u';

	/**
	 * The given value is valid if it matches the regular expression specified in PATTERN_VALIDCHARACTERS.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (preg_match(self::PATTERN_VALIDCHARACTERS, $value) === 0) {
			$this->addError('Only letters, numbers, spaces and certain punctuation marks are expected.', 1272298003);
		}
	}
}
