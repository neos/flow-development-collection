<?php
declare(ENCODING = 'utf-8');
namespace F3\Kickstart\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 package "Kickstart".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require(__DIR__ . '/../../Resources/Private/PHP/Sho_Inflect.php');

/**
 * Inflector utilities for the Kickstarter. This is a basic conversion from PHP
 * class and field names to a human readable form.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Inflector {

	/**
	 * @param string $word The word to pluralize
	 * @return string The pluralized word
	 * @author Christopher Hlubek
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
	 * @return The humanized value
	 * @author Christopher Hlubek <hlubek@networkteam.com>
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
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function spacify($camelCased, $glue = ' ') {
		return preg_replace('/([a-z0-9])([A-Z])/', '$1' . $glue . '$2', $camelCased);
	}
}
?>