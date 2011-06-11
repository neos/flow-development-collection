<?php
namespace F3\FLOW3\I18n\Formatter;

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
 * An interface for formatters.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Karol Gusak <firstname@lastname.eu>
 * @api
 */
interface FormatterInterface {

	/**
	 * Formats provided value using optional style properties
	 *
	 * @param mixed $value Formatter-specific variable to format (can be integer, \DateTime, etc)
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
	 * @return string String representation of $value provided, or (string)$value
	 * @api
	 */
	public function format($value, \F3\FLOW3\I18n\Locale $locale, array $styleProperties = array());
}

?>