<?php
namespace TYPO3\Flow\Http\Component;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Utility\Ip as IpUtility;

/**
 * HTTP component that checks request headers against a configured list of trusted proxy IP addresses.
 */
class TrustedProxiesComponent implements ComponentInterface
{
    const HEADER_CLIENT_IP = 'clientIp';
    const HEADER_HOST = 'host';
    const HEADER_PORT = 'port';
    const HEADER_PROTOCOL = 'proto';

    /**
     * @Flow\InjectConfiguration("http.trustedProxies")
     * @var array
     */
    protected $settings;

    /**
     * @param ComponentContext $componentContext
     * @return void
     * @api
     */
    public function handle(ComponentContext $componentContext)
    {
        $trustedRequest = clone $componentContext->getHttpRequest();

        if ($this->isFromTrustedProxy($trustedRequest)) {
            $trustedRequest = $trustedRequest->fromTrustedProxy();
        }

        $trustedRequest = $trustedRequest->withClientIpAddress($this->getTrustedClientIpAddress($trustedRequest));

        $protocolHeader = $this->getFirstTrustedProxyHeaderValue(self::HEADER_PROTOCOL, $trustedRequest);
        if ($protocolHeader !== null) {
            $trustedRequest->getUri()->setScheme($protocolHeader);
        }

        $hostHeader = $this->getFirstTrustedProxyHeaderValue(self::HEADER_HOST, $trustedRequest);
        if ($hostHeader !== null) {
            $trustedRequest->getUri()->setHost($hostHeader);
        }

        $portHeader = $this->getFirstTrustedProxyHeaderValue(self::HEADER_PORT, $trustedRequest);
        if ($portHeader !== null) {
            $trustedRequest->getUri()->setPort($portHeader);
        } elseif ($protocolHeader !== null) {
            $trustedRequest->getUri()->setPort($protocolHeader === 'https' ? 443 : 80);
        }

        $componentContext->replaceHttpRequest($trustedRequest);
    }

    /**
     * Get the values of trusted proxy header.
     *
     * @param string $type One of the HEADER_* constants
     * @param Request $request The request to get the trusted proxy header from
     * @return array|null An array of the values for this header type or NULL if this header type should not be trusted
     */
    protected function getTrustedProxyHeaderValues($type, Request $request)
    {
        $trustedHeaders = isset($this->settings['headers'][$type]) ? $this->settings['headers'][$type] : '';
        if ($trustedHeaders === '' || !$request->isFromTrustedProxy()) {
            return null;
        }
        $trustedHeaders = array_map('trim', explode(',', $trustedHeaders));

        foreach ($trustedHeaders as $trustedHeader) {
            if ($request->hasHeader($trustedHeader)) {
                return array_map('trim', explode(',', $request->getHeader($trustedHeader)));
            }
        }

        return null;
    }

    /**
     * Convenience getter for the first value of a given trusted proxy header.
     *
     * @param string $type One of the HEADER_* constants
     * @param Request $request The request to get the trusted proxy header from
     * @return mixed|null The first value of this header type or NULL if this header type should not be trusted
     */
    protected function getFirstTrustedProxyHeaderValue($type, Request $request)
    {
        $values = $this->getTrustedProxyHeaderValues($type, $request);
        return $values !== null ? reset($values) : null;
    }

    /**
     * Check if the given IP address is from a trusted proxy.
     *
     * @param string $ipAddress
     * @return bool
     */
    protected function ipIsTrustedProxy($ipAddress)
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        if ($this->settings['proxies'] === '*') {
            return true;
        }
        foreach ($this->settings['proxies'] as $ipPattern) {
            if (IpUtility::cidrMatch($ipAddress, $ipPattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the given request is from a trusted proxy.
     *
     * @param Request $request
     * @return bool If the server REMOTE_ADDR is from a trusted proxy
     */
    protected function isFromTrustedProxy(Request $request)
    {
        $server = $request->getServerParams();
        if (!isset($server['REMOTE_ADDR'])) {
            return false;
        }
        return $this->ipIsTrustedProxy($server['REMOTE_ADDR']);
    }

    /**
     * Get the most trusted client's IP address.
     *
     * This is the right-most address in the trusted client IP header, that is not a trusted proxy address.
     * If all proxies are trusted, this is the left-most address in the header.
     * If no proxies are trusted or no client IP header is trusted, this is the remote address of the machine
     * directly connected to the server.
     *
     * @return string|bool The most trusted client's IP address or FALSE if no remote address can be found
     */
    protected function getTrustedClientIpAddress(Request $request)
    {
        $server = $request->getServerParams();
        if (!isset($server['REMOTE_ADDR'])) {
            return false;
        }

        $trustedIpHeader = $this->getTrustedProxyHeaderValues(self::HEADER_CLIENT_IP, $request);
        if ($trustedIpHeader === null || $this->settings['proxies'] === []) {
            return $server['REMOTE_ADDR'];
        }

        if ($this->settings['proxies'] === '*') {
            return reset($trustedIpHeader);
        }

        $ipAddress = false;
        foreach (array_reverse($trustedIpHeader) as $headerIpAddress) {
            $portPosition = strpos($headerIpAddress, ':');
            $ipAddress = $portPosition !== false ? substr($headerIpAddress, 0, $portPosition) : $headerIpAddress;
            if (!$this->ipIsTrustedProxy($ipAddress)) {
                break;
            }
        }

        return $ipAddress;
    }
    
}
