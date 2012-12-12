<?php
namespace TYPO3\Kickstart\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Kickstart".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Inflector utilities for the Kickstarter. This is a basic conversion from PHP
 * class and field names to a human readable form.
 *
 */
class Inflector {

	/**
	 * @param string $word The word to pluralize
	 * @return string The pluralized word
	 */
	public function pluralize($word) {
		return \Sho_Inflect::pluralize($word);
	}

	/**
	 * Convert a model class name like "BlogAuthor" or a field name like
	 * "blogAuthor" to a humanized version like "Blog author" for better readability.
	 *
	 * @param string $camelCased The camel cased value
	 * @param boolean $lowercase Return lowercase value
	 * @return string The humanized value
	 */
	public function humanizeCamelCase($camelCased, $lowercase = FALSE) {
		$spacified = $this->spacify($camelCased);
		$result = strtolower($spacified);
		if (!$lowercase) {
			$result = ucfirst($result);
		}
		return $result;
	}

	/**
	 * Splits a string at lowercase/uppcase transitions and insert the glue
	 * character in between.
	 *
	 * @param string $camelCased
	 * @param string $glue
	 * @return string
	 */
	protected function spacify($camelCased, $glue = ' ') {
		return preg_replace('/([a-z0-9])([A-Z])/', '$1' . $glue . '$2', $camelCased);
	}
}
?>