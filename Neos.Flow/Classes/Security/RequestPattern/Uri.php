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
 * This class holds an URI pattern an decides, if a \Neos\Flow\Mvc\ActionRequest object matches against this pattern
 */
class Uri implements RequestPatternInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Expects options in the form array('uriPattern' => '<URI pattern>')
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Sets an URI pattern (preg_match() syntax)
     *
     * Note: the pattern is a full-on regular expression pattern. The only
     * thing that is touched by the code: forward slashes are escaped before
     * the pattern is used.
     *
     * @param string $uriPattern The URI pattern
     * @return void
     * @deprecated since 3.3 this is not used - use options instead (@see __construct())
     */
    public function setPattern($uriPattern)
    {
        $this->options['uriPattern'] = $uriPattern;
    }

    /**
     * Matches a \Neos\Flow\Mvc\RequestInterface against its set URL pattern rules
     *
     * @param RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     * @throws InvalidRequestPatternException
     */
    public function matchRequest(RequestInterface $request)
    {
        if (!$request instanceof ActionRequest) {
            return false;
        }
        if (!isset($this->options['uriPattern'])) {
            throw new InvalidRequestPatternException('Missing option "uriPattern" in the Uri request pattern configuration', 1446224530);
        }
        return (boolean)preg_match('/^' . str_replace('/', '\/', $this->options['uriPattern']) . '$/', $request->getHttpRequest()->getUri()->getPath());
    }
}
