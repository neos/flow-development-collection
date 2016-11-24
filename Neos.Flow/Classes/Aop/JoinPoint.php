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

use Neos\Flow\Aop\Exception\InvalidArgumentException;

/**
 * In Flow the join point object contains context information when a point cut
 * matches and the registered advices are executed.
 *
 * @api
 */
class JoinPoint implements JoinPointInterface
{
    /**
     * A reference to the proxy object
     * @var object
     */
    protected $proxy;

    /**
     * Class name of the target class this join point refers to
     * @var string
     */
    protected $className;

    /**
     * Method name of the target method which is about to or has been invoked
     * @var string
     */
    protected $methodName;

    /**
     * Array of method arguments which have been passed to the target method
     * @var array
     */
    protected $methodArguments;

    /**
     * The advice chain for this join point
     *
*@var \Neos\Flow\Aop\Advice\AdviceChain
     */
    protected $adviceChain;

    /**
     * The result of the method invocations (only used for After Returning advices)
     * @var mixed
     */
    protected $result = null;

    /**
     * The exception thrown (only used for After Throwing advices)
     * @var \Exception
     */
    protected $exception = null;

    /**
     * Constructor, creates the join point
     *
     * @param object $proxy Reference to the proxy class instance of the target class
     * @param string $className Class name of the target class this join point refers to
     * @param string $methodName Method name of the target method which is about to or has been invoked
     * @param array $methodArguments Array of method arguments which have been passed to the target method
     * @param \Neos\Flow\Aop\Advice\AdviceChain $adviceChain The advice chain for this join point
     * @param mixed $result The result of the method invocations (only used for After Returning advices)
     * @param \Exception $exception The exception thrown (only used for After Throwing advices)
     */
    public function __construct($proxy, $className, $methodName, array $methodArguments, Advice\AdviceChain $adviceChain = null, $result = null, \Exception $exception = null)
    {
        $this->proxy = $proxy;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->methodArguments = $methodArguments;
        $this->adviceChain = $adviceChain;
        $this->result = $result;
        $this->exception = $exception;
    }

    /**
     * Returns the reference to the proxy class instance
     *
     * @return \Neos\Flow\ObjectManagement\Proxy\ProxyInterface
     * @api
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Returns the class name of the target class this join point refers to
     *
     * @return string The class name
     * @api
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Returns the method name of the method this join point refers to
     *
     * @return string The method name
     * @api
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /**
     * Returns an array of arguments which have been passed to the target method
     *
     * @return array Array of arguments
     * @api
     */
    public function getMethodArguments()
    {
        return $this->methodArguments;
    }

    /**
     * Returns the value of the specified method argument
     *
     * @param  string $argumentName Name of the argument
     * @return mixed Value of the argument
     * @throws Exception\InvalidArgumentException
     * @api
     */
    public function getMethodArgument($argumentName)
    {
        if (!array_key_exists($argumentName, $this->methodArguments)) {
            throw new InvalidArgumentException('The argument "' . $argumentName . '" does not exist in method ' . $this->className . '->' . $this->methodName, 1172750905);
        }
        return $this->methodArguments[$argumentName];
    }

    /**
     * Sets the value of the specified method argument
     *
     * @param string $argumentName Name of the argument
     * @param mixed $argumentValue Value of the argument
     * @return void
     * @throws Exception\InvalidArgumentException
     * @api
     */
    public function setMethodArgument($argumentName, $argumentValue)
    {
        if (!array_key_exists($argumentName, $this->methodArguments)) {
            throw new InvalidArgumentException('The argument "' . $argumentName . '" does not exist in method ' . $this->className . '->' . $this->methodName, 1309260269);
        }
        $this->methodArguments[$argumentName] = $argumentValue;
    }

    /**
     * Returns TRUE if the argument with the specified name exists in the
     * method call this joinpoint refers to.
     *
     * @param  string $argumentName Name of the argument to check
     * @return boolean TRUE if the argument exists
     * @api
     */
    public function isMethodArgument($argumentName)
    {
        return isset($this->methodArguments[$argumentName]);
    }

    /**
     * Returns the advice chain related to this join point
     *
     * @return \Neos\Flow\Aop\Advice\AdviceChain The advice chain
     * @api
     */
    public function getAdviceChain()
    {
        return $this->adviceChain;
    }

    /**
     * If an exception was thrown by the target method
     * Only makes sense for After Throwing advices.
     *
     * @return boolean
     * @api
     */
    public function hasException()
    {
        return $this->exception !== null;
    }

    /**
     * Returns the exception which has been thrown in the target method.
     * If no exception has been thrown, NULL is returned.
     * Only makes sense for After Throwing advices.
     *
     * @return mixed The exception thrown or NULL
     * @api
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Returns the result of the method invocation. The result is only
     * available for AfterReturning advices.
     *
     * @return mixed Result of the method invocation
     * @api
     */
    public function getResult()
    {
        return $this->result;
    }
}
