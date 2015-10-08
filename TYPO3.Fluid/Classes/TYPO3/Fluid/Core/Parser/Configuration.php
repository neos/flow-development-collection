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
     * Note: Before 3.0 these interceptors where used for escaping, too. Escaping related interceptors are now expected to be stored in $escapingInterceptors (so that they can be disabled independently)
     *
     * @var array<\SplObjectStorage>
     */
    protected $interceptors = array();

    /**
     * Escaping interceptors registered with the configuration.
     *
     * @var array<\SplObjectStorage>
     */
    protected $escapingInterceptors = array();

    /**
     * Adds an interceptor to apply to values coming from object accessors.
     *
     * @param InterceptorInterface $interceptor
     * @return void
     */
    public function addInterceptor(InterceptorInterface $interceptor)
    {
        $this->addInterceptorToArray($interceptor, $this->interceptors);
    }

    /**
     * Adds an escaping interceptor to apply to values coming from object accessors if escaping is enabled
     *
     * @param InterceptorInterface $interceptor
     * @return void
     */
    public function addEscapingInterceptor(InterceptorInterface $interceptor)
    {
        $this->addInterceptorToArray($interceptor, $this->escapingInterceptors);
    }

    /**
     * Adds an interceptor to apply to values coming from object accessors.
     *
     * @param InterceptorInterface $interceptor
     * @param array $interceptorArray
     * @return void
     */
    protected function addInterceptorToArray(InterceptorInterface $interceptor, array &$interceptorArray)
    {
        foreach ($interceptor->getInterceptionPoints() as $interceptionPoint) {
            if (!isset($interceptorArray[$interceptionPoint])) {
                $interceptorArray[$interceptionPoint] = new \SplObjectStorage();
            }
            /** @var $interceptors \SplObjectStorage */
            $interceptors = $interceptorArray[$interceptionPoint];
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
        return isset($this->interceptors[$interceptionPoint]) ? $this->interceptors[$interceptionPoint] : new \SplObjectStorage();
    }

    /**
     * Returns all escaping interceptors for a given Interception Point.
     *
     * @param integer $interceptionPoint one of the \TYPO3\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_* constants,
     * @return \SplObjectStorage<\TYPO3\Fluid\Core\Parser\InterceptorInterface>
     */
    public function getEscapingInterceptors($interceptionPoint)
    {
        return isset($this->escapingInterceptors[$interceptionPoint]) ? $this->escapingInterceptors[$interceptionPoint] : new \SplObjectStorage();
    }
}
