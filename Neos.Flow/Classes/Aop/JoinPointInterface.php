<?php
namespace Neos\Flow\Aop;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for a join point
 *
 */
interface JoinPointInterface
{
    /**
     * Returns the reference to the proxy class instance
     *
     * @return \Neos\Flow\ObjectManagement\Proxy\ProxyInterface
     */
    public function getProxy();

    /**
     * Returns the class name of the target class this join point refers to
     *
     * @return string The class name
     */
    public function getClassName();

    /**
     * Returns the method name of the method this join point refers to
     *
     * @return string The method name
     */
    public function getMethodName();

    /**
     * Returns an array of arguments which have been passed to the target method
     *
     * @return array Array of arguments
     */
    public function getMethodArguments();

    /**
     * Returns the value of the specified method argument
     *
     * @param  string $argumentName Name of the argument
     * @return mixed Value of the argument
     */
    public function getMethodArgument($argumentName);

    /**
     * Returns TRUE if the argument with the specified name exists in the
     * method call this joinpoint refers to.
     *
     * @param string $argumentName Name of the argument to check
     * @return boolean TRUE if the argument exists
     */
    public function isMethodArgument($argumentName);

    /**
     * Sets the value of the specified method argument
     *
     * @param string $argumentName Name of the argument
     * @param mixed $argumentValue Value of the argument
     * @return void
     */
    public function setMethodArgument($argumentName, $argumentValue);

    /**
     * Returns the advice chain related to this join point
     *
     * @return \Neos\Flow\Aop\Advice\AdviceChain The advice chain
     */
    public function getAdviceChain();

    /**
     * If an exception was thrown by the target method
     * Only makes sense for After Throwing advices.
     *
     * @return boolean
     */
    public function hasException();

    /**
     * Returns the exception which has been thrown in the target method.
     * If no exception has been thrown, NULL is returned.
     * Only makes sense for After Throwing advices.
     *
     * @return \Exception The exception thrown or NULL
     */
    public function getException();

    /**
     * Returns the result of the method invocation. The result is only
     * available for AfterReturning advices.
     *
     * @return mixed Result of the method invocation
     */
    public function getResult();
}
