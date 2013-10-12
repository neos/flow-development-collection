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
 * Validator for Universally Unique Identifiers.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class UuidValidator extends AbstractValidator {

	/**
	 * A preg pattern to match against UUIDs
	 * @var string
	 */
	const PATTERN_MATCH_UUID = '/^([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}$/';

	/**
	 * Checks if the given value is a syntactically valid UUID.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (!is_string($value) || !preg_match(self::PATTERN_MATCH_UUID, $value)) {
			$this->addError('The given subject was not a valid UUID.', 1221565853);
		}
	}
}
