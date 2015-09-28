<?php
namespace TYPO3\Flow\Tests\Persistence\Fixture\Model;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A model fixture used for testing the persistence manager
 *
 * @Flow\Entity
 */
class Entity2 implements \TYPO3\Flow\Aop\ProxyInterface
{
    /**
     * Just a normal string
     *
     * @var string
     */
    public $someString;

    /**
     * @var integer
     */
    public $someInteger;

    /**
     * @var \TYPO3\Flow\Tests\Persistence\Fixture\Model\Entity3
     */
    public $someReference;

    /**
     * @var array
     */
    public $someReferenceArray = array();

    /**
     * Invokes the joinpoint - calls the target methods.
     *
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The join point
     * @return mixed Result of the target (ie. original) method
     */
    public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
    }

    /**
     * A stub to satisfy the Flow Proxy Interface
     */
    public function __wakeup()
    {
    }
}
