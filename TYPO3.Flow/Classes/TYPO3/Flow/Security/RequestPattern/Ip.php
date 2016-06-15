<?php
namespace TYPO3\Flow\Security\RequestPattern;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Security\RequestPatternInterface;
use TYPO3\Flow\Utility\Ip as IpUtility;

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
class Ip implements RequestPatternInterface
{
    /**
     * The CIDR styled IP pattern
     *
     * @var string
     */
    protected $ipPattern = '';

    /**
     * @return string The set pattern
     */
    public function getPattern()
    {
        return $this->ipPattern;
    }

    /**
     * Sets an IP pattern (CIDR syntax)
     *
     * @param string $ipPattern The CIDR styled IP pattern
     * @return void
     */
    public function setPattern($ipPattern)
    {
        $this->ipPattern = $ipPattern;
    }

    /**
     * Matches a \TYPO3\Flow\Mvc\RequestInterface against the set IP pattern rules
     *
     * @param RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     */
    public function matchRequest(RequestInterface $request)
    {
        if (!$request instanceof ActionRequest) {
            return false;
        }
        return IpUtility::cidrMatch($request->getHttpRequest()->getClientIpAddress(), $this->ipPattern);
    }
}
