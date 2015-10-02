<?php
namespace TYPO3\Fluid\Core\Parser;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The parser configuration. Contains all configuration needed to configure
 * the building of a SyntaxTree.
 */
class Configuration
{
    /**
     * Generic interceptors registered with the configuration.
     *
     * @var array<\SplObjectStorage>
     */
    protected $interceptors = array();

    /**
     * Adds an interceptor to apply to values coming from object accessors.
     *
     * @param InterceptorInterface $interceptor
     * @return void
     */
    public function addInterceptor(InterceptorInterface $interceptor)
    {
        foreach ($interceptor->getInterceptionPoints() as $interceptionPoint) {
            if (!isset($this->interceptors[$interceptionPoint])) {
                $this->interceptors[$interceptionPoint] = new \SplObjectStorage();
            }
            /** @var $interceptors \SplObjectStorage */
            $interceptors = $this->interceptors[$interceptionPoint];
            if (!$interceptors->contains($interceptor)) {
                $interceptors->attach($interceptor);
            }
        }
    }

    /**
     * Returns all interceptors for a given Interception Point.
     *
     * @param integer $interceptionPoint one of the \TYPO3\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_* constants,
     * @return \SplObjectStorage<\TYPO3\Fluid\Core\Parser\InterceptorInterface>
     */
    public function getInterceptors($interceptionPoint)
    {
        if (isset($this->interceptors[$interceptionPoint]) && $this->interceptors[$interceptionPoint] instanceof \SplObjectStorage) {
            return $this->interceptors[$interceptionPoint];
        }
        return new \SplObjectStorage();
    }
}
