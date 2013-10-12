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


/**
 * Validator for checking Date and Time boundaries
 *
 * @api
 */
class DateTimeRangeValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'latestDate' => array(NULL, 'The latest date to accept', 'string'),
		'earliestDate' => array(NULL, 'The earliest date to accept', 'string')
	);

	/**
	 * Adds errors if the given DateTime does not match the set boundaries.
	 *
	 * latestDate and earliestDate may be each <time>, <start>/<duration> or <duration>/<end>, where <duration> is an
	 * ISO 8601 duration and <start> or <end> or <time> may be 'now' or a PHP supported format. (1)
	 *
	 * In general, you are able to provide a timestamp or a timestamp with additional calculation. Calculations are done
	 * as described in ISO 8601 (2), with an introducing "P". P7MT2H30M for example mean a period of 7 months, 2 hours
	 * and 30 minutes (P introduces a period at all, while a following T introduces the time-section of a period. This
	 * is not at least in order not to confuse months and minutes, both represented as M).
	 * A period is separated from the timestamp with a forward slash "/". If the period follows the timestamp, that
	 * period is added to the timestamp; if the period precedes the timestamp, it's subtracted.
	 * The timestamp can be one of PHP's supported date formats (1), so also "now" is supported.
	 *
	 * Use cases:
	 *
	 * If you offer something that has to be manufactured and you ask for a delivery date, you might assure that this
	 * date is at least two weeks in advance; this could be done with the expression "now/P2W".
	 * If you have a library of ancient goods and want to track a production date that is at least 5 years ago, you can
	 * express it with "P5Y/now".
	 *
	 * Examples:
	 *
	 * If you want to test if a given date is at least five minutes ahead, use
	 *   earliestDate: now/PT5M
	 * If you want to test if a given date was at least 10 days ago, use
	 *   latestDate: P10D/now
	 * If you want to test if a given date is between two fix boundaries, just combine the latestDate and earliestDate-options:
	 *   earliestDate: 2007-03-01T13:00:00Z
	 *   latestDate: 2007-03-30T13:00:00Z
	 *
	 * Footnotes:
	 *
	 * http://de.php.net/manual/en/datetime.formats.compound.php (1)
	 * http://en.wikipedia.org/wiki/ISO_8601#Durations (2)
	 * http://en.wikipedia.org/wiki/ISO_8601#Time_intervals (3)
	 *
	 * @param mixed $dateTime The DateTime value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($dateTime) {
		if (!$dateTime instanceof \DateTime) {
			$this->addError('The given value was not a valid date', 1324314378);
			return;
		}
		$earliestDate = isset($this->options['earliestDate']) ? $this->parseReferenceDate($this->options['earliestDate']) : NULL;
		$latestDate = isset($this->options['latestDate']) ? $this->parseReferenceDate($this->options['latestDate']) : NULL;

		if (isset($earliestDate) && isset($latestDate)) {
			if ($dateTime < $earliestDate || $dateTime > $latestDate) {
				$this->addError('The given date must be between %s and %s', 1325615630, array($earliestDate->format('Y-m-d H:i:s'), $latestDate->format('Y-m-d H:i:s')));
			}
		} elseif (isset($earliestDate)) {
			if ($dateTime < $earliestDate) {
				$this->addError('The given date must be after %s', 1324315107, array($earliestDate->format('Y-m-d H:i:s')));
			}
		} elseif (isset($latestDate)) {
			if ($dateTime > $latestDate) {
				$this->addError('The given date must be before %s', 1324315115, array($latestDate->format('Y-m-d H:i:s')));
			}
		}
	}

	/**
	 * Calculates a DateTime object from a given Time interval
	 *
	 * @param string $referenceDateString being one of <time>, <start>/<offset> or <offset>/<end>
	 * @return \DateTime
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
	 * @see isValid()
	 */
	protected function parseReferenceDate($referenceDateString) {
		$referenceDateParts = explode('/', $referenceDateString, 2);

		if (count($referenceDateParts) === 1) {
				// assume a valid Date/Time string
			return new \DateTime($referenceDateParts[0]);
		}
			// check if the period (the interval) is the first or second item:
		if (strpos($referenceDateParts[0], 'P') === 0) {
			$interval = new \DateInterval($referenceDateParts[0]);
			$date = new \DateTime($referenceDateParts[1]);
			return $date->sub($interval);
		} elseif (strpos($referenceDateParts[1], 'P') === 0) {
			$interval = new \DateInterval($referenceDateParts[1]);
			$date = new \DateTime($referenceDateParts[0]);
			return $date->add($interval);
		} else {
			throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException(sprintf('There is no valid interval declaration in "%s". Exactly one part must begin with "P".', $referenceDateString), 1324314462);
		}
	}
}
