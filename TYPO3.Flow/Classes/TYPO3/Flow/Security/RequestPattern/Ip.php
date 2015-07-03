<?php
namespace TYPO3\Flow\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Security\RequestPatternInterface;

/**
 * This class holds a CIDR IP pattern an decides, if a \TYPO3\Flow\Mvc\RequestInterface object matches against this pattern,
 * comparing the client IP address.
 *
 * The pattern can contain IPv4 and IPv6 addresses (including IPv6 wrapped IPv4 addresses).
 * @see http://tools.ietf.org/html/rfc4632
 * @see http://tools.ietf.org/html/rfc4291#section-2.3
 *
 * Example: 127.0.0.0/24 will match all IP addresses from 127.0.0.0 to 127.0.0.255
 *          127.0.0.0/31 and 127.0.0.1/31 will both match the IP addresses 127.0.0.0 and 127.0.0.1
 *          127.0.0.254/31 and 127.0.0.255/31 will both match the IP addresses 127.0.0.254 and 127.0.0.255
 *          1:2::3:4 will match the IPv6 address written as 1:2:0:0:0:0:3:4 or 1:2::3:4
 *          ::7F00:1 will match the address written as 127.0.0.1, ::127.0.0.1 or ::7F00:1
 *          ::1 (IPv6 loopback) will *not* match the address 127.0.0.1
 */
class Ip implements RequestPatternInterface {

	/**
	 * The CIDR styled IP pattern
	 *
	 * @var string
	 */
	protected $ipPattern = '';

	/**
	 * @return string The set pattern
	 */
	public function getPattern() {
		return $this->ipPattern;
	}

	/**
	 * Sets an IP pattern (CIDR syntax)
	 *
	 * @param string $ipPattern The CIDR styled IP pattern
	 * @return void
	 */
	public function setPattern($ipPattern) {
		$this->ipPattern = $ipPattern;
	}

	/**
	 * Matches a CIDR range pattern against an IP
	 *
	 * @param string $ip The IP to match
	 * @param string $range The CIDR range pattern to match against
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 */
	protected function cidrMatch($ip, $range) {
		if (strpos($range, '/') === FALSE) {
			$bits = NULL;
			$subnet = $range;
		} else {
			list ($subnet, $bits) = explode('/', $range);
		}

		$ip = inet_pton($ip);
		$subnet = inet_pton($subnet);
		if ($ip === FALSE || $subnet === FALSE) {
			return FALSE;
		}

		if (strlen($ip) > strlen($subnet)) {
			$subnet = str_pad($subnet, strlen($ip), chr(0), STR_PAD_LEFT);
		} elseif (strlen($subnet) > strlen($ip)) {
			$ip = str_pad($ip, strlen($subnet), chr(0), STR_PAD_LEFT);
		}

		if ($bits === NULL) {
			return ($ip === $subnet);
		} else {
			for ($i = 0; $i < strlen($ip); $i++) {
				$mask = 0;
				if ($bits > 0) {
					$mask = ($bits >= 8) ? 255 : (256 - (1 << (8 - $bits)));
					$bits -= 8;
				}
				if ((ord($ip[$i]) & $mask) !== (ord($subnet[$i]) & $mask)) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Matches a \TYPO3\Flow\Mvc\RequestInterface against the set IP pattern rules
	 *
	 * @param RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 */
	public function matchRequest(RequestInterface $request) {
		if (!$request instanceof ActionRequest) {
			return FALSE;
		}
		return (boolean)$this->cidrMatch($request->getHttpRequest()->getClientIpAddress(), $this->ipPattern);
	}
}
