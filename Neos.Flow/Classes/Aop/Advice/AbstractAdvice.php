<?php
namespace Neos\Flow\Aop\Advice;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\SignalSlot\Dispatcher;

/**
 * Base class for Advices.
 *
 */
class AbstractAdvice implements AdviceInterface
{
    /**
     * Holds the name of the aspect object containing the advice
     * @var string
     */
    protected $aspectObjectName;

    /**
     * Contains the name of the advice method
     * @var string
     */
    protected $adviceMethodName;

    /**
     * A reference to the SignalSlot Dispatcher
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * A reference to the Object Manager
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Runtime evaluations definition array
     * @var array
     */
    protected $runtimeEvaluationsDefinition;

    /**
     * Runtime evaluations function
     * @var \Closure
     */
    protected $runtimeEvaluator;

    /**
     * Constructor
     *
     * @param string $aspectObjectName Name of the aspect object containing the advice
     * @param string $adviceMethodName Name of the advice method
     * @param ObjectManagerInterface $objectManager Only require if a runtime evaluations function is specified
     * @param \Closure $runtimeEvaluator Runtime evaluations function
     */
    public function __construct($aspectObjectName, $adviceMethodName, ObjectManagerInterface $objectManager = null, \Closure $runtimeEvaluator = null)
    {
        $this->aspectObjectName = $aspectObjectName;
        $this->adviceMethodName = $adviceMethodName;
        $this->objectManager = $objectManager;
        $this->runtimeEvaluator = $runtimeEvaluator;
    }

    /**
     * Invokes the advice method
     *
     * @param JoinPointInterface $joinPoint The current join point which is passed to the advice method
     * @return mixed Result of the advice method
     */
    public function invoke(JoinPointInterface $joinPoint)
    {
        if ($this->runtimeEvaluator !== null && $this->runtimeEvaluator->__invoke($joinPoint, $this->objectManager) === false) {
            return;
        }

        $adviceObject = $this->objectManager->get($this->aspectObjectName);
        $methodName = $this->adviceMethodName;
        $adviceObject->$methodName($joinPoint);

        $this->emitAdviceInvoked($adviceObject, $methodName, $joinPoint);
    }

    /**
     * Returns the aspect's object name which has been passed to the constructor
     *
     * @return string The object name of the aspect
     */
    public function getAspectObjectName()
    {
        return $this->aspectObjectName;
    }

    /**
     * Returns the advice's method name which has been passed to the constructor
     *
     * @return string The name of the advice method
     */
    public function getAdviceMethodName()
    {
        return $this->adviceMethodName;
    }

    /**
     * Emits a signal when an Advice is invoked
     *
     * The advice is not proxyable, so the signal is dispatched manually here.
     *
     * @param object $aspectObject
     * @param string $methodName
     * @param JoinPointInterface $joinPoint
     * @return void
     * @Flow\Signal
     */
    protected function emitAdviceInvoked($aspectObject, $methodName, $joinPoint)
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->objectManager->get(Dispatcher::class);
        }

        $this->dispatcher->dispatch(self::class, 'adviceInvoked', [$aspectObject, $methodName, $joinPoint]);
    }
}
