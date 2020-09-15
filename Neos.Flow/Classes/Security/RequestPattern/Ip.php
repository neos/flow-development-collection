<?php
namespace Neos\Flow\Security\RequestPattern;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Utility\Ip as IpUtility;

/**
 * This class holds a CIDR IP pattern an decides, if an ActionRequest object matches against this pattern,
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
     * @var array
     */
    protected $options;

    /**
     * Expects options in the form array('cidrPattern' => '<CIDR IP Pattern>')
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Matches an ActionRequest against the set IP pattern rules
     *
     * @param ActionRequest $request The request that should be matched
     * @return boolean true if the pattern matched, false otherwise
     * @throws InvalidRequestPatternException
     */
    public function matchRequest(ActionRequest $request)
    {
        if (!isset($this->options['cidrPattern'])) {
            throw new InvalidRequestPatternException('Missing option "cidrPattern" in the Ip request pattern configuration', 1446224520);
        }
        return IpUtility::cidrMatch($request->getHttpRequest()->getAttribute(ServerRequestAttributes::CLIENT_IP), $this->options['cidrPattern']);
    }
}
