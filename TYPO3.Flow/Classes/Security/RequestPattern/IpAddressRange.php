<?php
namespace TYPO3\FLOW3\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * This class holds an ipAddressRange pattern an decides, if a \TYPO3\FLOW3\Mvc\RequestInterface object matches against this pattern
 *
 */
class IpAddressRange implements \TYPO3\FLOW3\Security\RequestPatternInterface {

	/**
	 * @var string
	 */
	protected $ipAddressRange = '';

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 */
	public function canMatch(\TYPO3\FLOW3\Mvc\RequestInterface $request) {
		return TRUE;
	}

	/**
	 * Returns the set pattern
	 *
	 * @return string The set pattern
	 */
	public function getPattern() {
		return $this->ipAddressRange;
	}

	/**
	 * Sets an ip address range
	 *
	 * @param string $ipAddressRange The ip address range
	 * @return void
	 */
	public function setPattern($ipAddressRange) {
		$this->ipAddressRange = $ipAddressRange;
	}

	/**
	 * Matches a \TYPO3\FLOW3\Mvc\RequestInterface against its set ip address range
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws \TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 */
	public function matchRequest(\TYPO3\FLOW3\Mvc\RequestInterface $request) {
		return FALSE;
	}
}

?>