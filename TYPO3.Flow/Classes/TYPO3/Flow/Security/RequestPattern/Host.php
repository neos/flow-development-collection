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

/**
 * This class holds a host URI pattern and decides, if a \TYPO3\Flow\Mvc\RequestInterface object matches against this pattern
 * Note: the pattern is a simple wildcard matching pattern, with * as the wildcard character.
 *
 * Example: *.neos.io will match "flow.neos.io" and "www.neos.io", but not "neos.io"
 *          www.mydomain.* will match all TLDs of www.mydomain, but not "blog.mydomain.net" or "mydomain.com"
 */
class Host implements RequestPatternInterface
{
    /**
     * @var string
     */
    protected $hostPattern = '';

    /**
     * @return string The set pattern
     */
    public function getPattern()
    {
        return $this->hostPattern;
    }

    /**
     * @param string $hostPattern The host pattern
     * @return void
     */
    public function setPattern($hostPattern)
    {
        $this->hostPattern = $hostPattern;
    }

    /**
     * Matches a \TYPO3\Flow\Mvc\RequestInterface against its set host pattern rules
     *
     * @param RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     */
    public function matchRequest(RequestInterface $request)
    {
        if (!$request instanceof ActionRequest) {
            return false;
        }
        $hostPattern = str_replace('\\*', '.*', preg_quote($this->hostPattern, '/'));
        return preg_match('/^' . $hostPattern . '$/', $request->getHttpRequest()->getUri()->getHost()) === 1;
    }
}
