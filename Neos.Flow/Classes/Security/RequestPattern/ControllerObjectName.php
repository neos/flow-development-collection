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

use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\RequestPatternInterface;

/**
 * This class holds an controller object name pattern an decides, if a \Neos\Flow\Mvc\ActionRequest object matches against this pattern
 */
class ControllerObjectName implements RequestPatternInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Expects options in the form array('controllerObjectNamePattern' => '<regularExpression>')
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Sets an controller object name pattern (preg_match() syntax)
     *
     * @param string $controllerObjectNamePattern The preg_match() styled controller object name pattern
     * @return void
     * @deprecated since 3.3 this is not used - use options instead (@see __construct())
     */
    public function setPattern($controllerObjectNamePattern)
    {
        $this->options['controllerObjectNamePattern'] = $controllerObjectNamePattern;
    }

    /**
     * Matches a \Neos\Flow\Mvc\RequestInterface against its set controller object name pattern rules
     *
     * @param RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     * @throws InvalidRequestPatternException
     */
    public function matchRequest(RequestInterface $request)
    {
        if (!isset($this->options['controllerObjectNamePattern'])) {
            throw new InvalidRequestPatternException('Missing option "controllerObjectNamePattern" in the ControllerObjectName request pattern configuration', 1446224501);
        }
        return (boolean)preg_match('/^' . str_replace('\\', '\\\\', $this->options['controllerObjectNamePattern']) . '$/', $request->getControllerObjectName());
    }
}
