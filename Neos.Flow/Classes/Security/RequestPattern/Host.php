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
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\RequestPatternInterface;

/**
 * This class holds a host URI pattern and decides, if an ActionRequest object matches against this pattern
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
     * Matches an ActionRequest against its set host pattern rules
     *
     * @param ActionRequest $request The request that should be matched
     * @return boolean true if the pattern matched, false otherwise
     * @throws InvalidRequestPatternException
     */
    public function matchRequest(ActionRequest $request)
    {
        if (!isset($this->options['hostPattern'])) {
            throw new InvalidRequestPatternException('Missing option "hostPattern" in the Host request pattern configuration', 1446224510);
        }
        $hostPattern = str_replace('\\*', '.*', preg_quote($this->options['hostPattern'], '/'));
        return preg_match('/^' . $hostPattern . '$/', $request->getHttpRequest()->getUri()->getHost()) === 1;
    }
}
