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
     * Matches an ActionRequest against its set controller object name pattern rules
     *
     * @param ActionRequest $request The request that should be matched
     * @return boolean true if the pattern matched, false otherwise
     * @throws InvalidRequestPatternException
     */
    public function matchRequest(ActionRequest $request)
    {
        if (!isset($this->options['controllerObjectNamePattern'])) {
            throw new InvalidRequestPatternException('Missing option "controllerObjectNamePattern" in the ControllerObjectName request pattern configuration', 1446224501);
        }
        return (boolean)preg_match('/^' . str_replace('\\', '\\\\', $this->options['controllerObjectNamePattern']) . '$/', $request->getControllerObjectName());
    }
}
