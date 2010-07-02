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
 * Formatter for date and time.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class DatetimeFormatter implements \F3\FLOW3\Locale\Formatter\FormatterInterface {

	/**
	 * @var \F3\FLOW3\Locale\Cldr\Reader\DatesReader
	 */
	protected $datesReader;

	/**
	 * @param \F3\FLOW3\Locale\Cldr\Reader\DatesReader $datesReader
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectDatesReader(\F3\FLOW3\Locale\Cldr\Reader\DatesReader $datesReader) {
		$this->datesReader = $datesReader;
	}

	/**
	 * Formats provided value using optional style properties
	 *
	 * @param mixed $value Formatter-specific variable to format (can be integer, \DateTime, etc)
	 * @param \F3\FLOW3\Locale\Locale $locale Locale to use
	 * @param string $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
	 * @return string String representation of $value provided, or (string)$value
	 * @api
	 */
	public function format($value, \F3\FLOW3\Locale\Locale $locale, array $styleProperties = array()) {
		$style = (isset($styleProperties[0])) ? $styleProperties[0] : 'datetime';
		$length = (isset($styleProperties[1])) ? $styleProperties[1] : 'default';

		switch ($style) {
			case 'date':
				return $this->datesReader->formatDate($value, $locale, $length);
			case 'time':
				return $this->datesReader->formatTime($value, $locale, $length);
			default:
				return $this->datesReader->formatDateTime($value, $locale, $length);
		}
	}
}

?>