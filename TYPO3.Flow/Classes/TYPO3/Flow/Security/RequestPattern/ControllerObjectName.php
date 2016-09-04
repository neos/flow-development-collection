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

use TYPO3\Flow\Mvc\RequestInterface;

/**
 * This class holds an controller object name pattern an decides, if a \TYPO3\Flow\Mvc\ActionRequest object matches against this pattern
 *
 */
class ControllerObjectName implements \TYPO3\Flow\Security\RequestPatternInterface
{
    /**
     * The preg_match() styled controller object name pattern
     * @var string
     */
    protected $controllerObjectNamePattern = '';

    /**
     * Returns the set pattern
     *
     * @return string The set pattern
     */
    public function getPattern()
    {
        return $this->controllerObjectNamePattern;
    }

    /**
     * Sets an controller object name pattern (preg_match() syntax)
     *
     * @param string $controllerObjectNamePattern The preg_match() styled controller object name pattern
     * @return void
     */
    public function setPattern($controllerObjectNamePattern)
    {
        $this->controllerObjectNamePattern = $controllerObjectNamePattern;
    }

    /**
     * Matches a \TYPO3\Flow\Mvc\RequestInterface against its set controller object name pattern rules
     *
     * @param RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     */
    public function matchRequest(RequestInterface $request)
    {
        return (boolean)preg_match('/^' . str_replace('\\', '\\\\', $this->controllerObjectNamePattern) . '$/', $request->getControllerObjectName());
    }
}
