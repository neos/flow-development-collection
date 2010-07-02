<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\Formatter;

/* *
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
 * Formatter for numbers.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberFormatter {

	/**
	 * @var \F3\FLOW3\Locale\Cldr\Reader\NumbersReader
	 */
	protected $numbersReader;

	/**
	 * @param \F3\FLOW3\Locale\Cldr\Reader\NumbersReader $numbersReader
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectNumbersReader(\F3\FLOW3\Locale\Cldr\Reader\NumbersReader $numbersReader) {
		$this->numbersReader = $numbersReader;
	}

	/**
	 * Formats provided value using optional style properties
	 *
	 * @param mixed $value Formatter-specific variable to format (can be integer, \DateTime, etc)
	 * @param \F3\FLOW3\Locale\Locale $locale Locale to use
	 * @param string $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
	 * @return string String representation of $value provided, or (string)$value
	 */
	public function format($value, \F3\FLOW3\Locale\Locale $locale, array $styleProperties = array()) {
		$style = (isset($styleProperties[0])) ? $styleProperties[0] : 'decimal';
		$length = 'default';

		switch ($style) {
			case 'percent':
				return $this->numbersReader->formatPercentNumber($value, $locale, $length);
			default:
				return $this->numbersReader->formatDecimalNumber($value, $locale, $length);
		}
	}
}

?>