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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\RequestPatternInterface;

/**
 * This class holds a host URI pattern and decides, if a \Neos\Flow\Mvc\RequestInterface object matches against this pattern
 * Note: the pattern is a simple wildcard matching pattern, with * as the wildcard character.
 *
 * Example: *.neos.io will match "flow.neos.io" and "www.neos.io", but not "neos.io"
 *          www.mydomain.* will match all TLDs of www.mydomain, but not "blog.mydomain.net" or "mydomain.com"
 */
class Host implements RequestPatternInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Expects options in the form array('hostPattern' => '<host pattern>')
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $hostPattern The host pattern
     * @return void
     * @deprecated since 3.3 this is not used - use options instead (@see __construct())
     */
    public function setPattern($hostPattern)
    {
        $this->options['hostPattern'] = $hostPattern;
    }

    /**
     * Matches a \Neos\Flow\Mvc\RequestInterface against its set host pattern rules
     *
     * @param RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     * @throws InvalidRequestPatternException
     */
    public function matchRequest(RequestInterface $request)
    {
        if (!isset($this->options['hostPattern'])) {
            throw new InvalidRequestPatternException('Missing option "hostPattern" in the Host request pattern configuration', 1446224510);
        }
        if (!$request instanceof ActionRequest) {
            return false;
        }
        $hostPattern = str_replace('\\*', '.*', preg_quote($this->options['hostPattern'], '/'));
        return preg_match('/^' . $hostPattern . '$/', $request->getHttpRequest()->getUri()->getHost()) === 1;
    }
}
