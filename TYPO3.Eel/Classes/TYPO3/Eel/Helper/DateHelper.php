<?php
namespace TYPO3\Eel\Helper;

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
use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * Date helpers for Eel contexts
 *
 * @Flow\Proxy(false)
 */
class DateHelper implements ProtectedContextAwareInterface {

	/**
	 * Parse a date from string with a format to a DateTime object
	 *
	 * @param string $string
	 * @param string $format
	 * @return \DateTime
	 */
	public function parse($string, $format) {
		return \DateTime::createFromFormat($format, $string);
	}

	/**
	 * Format a date (or interval) to a string with a given format
	 *
	 * See formatting options as in PHP date()
	 *
	 * @param integer|string|\DateTime|\DateInterval $date
	 * @param string $format
	 * @return string
	 */
	public function format($date, $format) {
		if ($date instanceof \DateTime) {
			return $date->format($format);
		} elseif ($date instanceof \DateInterval) {
			return $date->format($format);
		} elseif ($date === 'now') {
			return date($format);
		} else {
			$timestamp = (integer)$date;
			return date($format, $timestamp);
		}
	}

	/**
	 * Get the current date and time
	 *
	 * Examples::
	 *
	 *     Date.now().timestamp
	 *
	 * @return \DateTime
	 */
	public function now() {
		return new \DateTime('now');
	}

	/**
	 * Get the current date
	 *
	 * @return \DateTime
	 */
	public function today() {
		return new \DateTime('today');
	}

	/**
	 * Add an interval to a date and return a new DateTime object
	 *
	 * @param \DateTime $date
	 * @param string|\DateInterval $interval
	 * @return \DateTime
	 */
	public function add($date, $interval) {
		if (!$interval instanceof \DateInterval) {
			$interval = new \DateInterval($interval);
		}
		$result = clone $date;
		return $result->add($interval);
	}

	/**
	 * Subtract an interval from a date and return a new DateTime object
	 *
	 * @param \DateTime $date
	 * @param string|\DateInterval $interval
	 * @return \DateTime
	 */
	public function subtract($date, $interval) {
		if (!$interval instanceof \DateInterval) {
			$interval = new \DateInterval($interval);
		}
		$result = clone $date;
		return $result->sub($interval);
	}

	/**
	 * Get the difference between two dates as a \DateInterval object
	 *
	 * @param \DateTime $dateA
	 * @param \DateTime $dateB
	 * @return \DateInterval
	 */
	public function diff($dateA, $dateB) {
		return $dateA->diff($dateB);
	}

	/**
	 * Get the day of month of a date
	 *
	 * @param \DateTime $dateTime
	 * @return integer The day of month of the given date
	 */
	public function dayOfMonth(\DateTime $dateTime) {
		return (integer)$dateTime->format('d');
	}

	/**
	 * Get the month of a date
	 *
	 * @param \DateTime $dateTime
	 * @return integer The month of the given date
	 */
	public function month(\DateTime $dateTime) {
		return (integer)$dateTime->format('m');
	}

	/**
	 * Get the year of a date
	 *
	 * @param \DateTime $dateTime
	 * @return integer The year of the given date
	 */
	public function year(\DateTime $dateTime) {
		return (integer)$dateTime->format('Y');
	}

	/**
	 * Get the hour of a date (24 hour format)
	 *
	 * @param \DateTime $dateTime
	 * @return integer The hour of the given date
	 */
	public function hour(\DateTime $dateTime) {
		return (integer)$dateTime->format('H');
	}

	/**
	 * Get the minute of a date
	 *
	 * @param \DateTime $dateTime
	 * @return integer The minute of the given date
	 */
	public function minute(\DateTime $dateTime) {
		return (integer)$dateTime->format('i');
	}

	/**
	 * Get the second of a date
	 *
	 * @param \DateTime $dateTime
	 * @return integer The second of the given date
	 */
	public function second(\DateTime $dateTime) {
		return (integer)$dateTime->format('s');
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}
}
