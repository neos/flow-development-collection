<?php
namespace TYPO3\Eel\Helper\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Factory methods for TranslationParameterToken
 *
 * @Flow\Scope("singleton")
 */
class TranslationParameterTokenFactory {

	/**
	 * Create a new TranslationParameterToken
	 *
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function create() {
		return new TranslationParameterToken();
	}

	/**
	 * Create a new TranslationParameterToken and Initialize it with an id
	 *
	 * @param string $id
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function createWithId($id) {
		$translationParameterToken = new TranslationParameterToken();
		return $translationParameterToken->id($id);
	}

	/**
	 * Create a new TranslationParameterToken and Initialize it with a value
	 *
	 * @param string $value
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function createWithValue($value) {
		$translationParameterToken = new TranslationParameterToken();
		return $translationParameterToken->value($value);
	}
}
