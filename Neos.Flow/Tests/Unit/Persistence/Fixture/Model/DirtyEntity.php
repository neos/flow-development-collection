<?php
namespace Neos\Flow\Tests\Persistence\Fixture\Model;

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
use Neos\Flow\Aop\ProxyInterface;

/**
 * A model fixture used for testing the persistence manager
 *
 * @Flow\Entity
 */
class DirtyEntity implements ProxyInterface
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
     * Invokes the joinpoint - calls the target methods.
     *
     * @param JoinPointInterface $joinPoint The join point
     * @return mixed Result of the target (ie. original) method
     */
    public function Flow_Aop_Proxy_invokeJoinPoint(JoinPointInterface $joinPoint)
    {
    }

    /**
     * Returns TRUE as this is a DirtyEntity
     *
     * @return boolean
     */
    public function Flow_Persistence_isDirty()
    {
        return true;
    }

    /**
     * Dummy method for mock creation
     * @param string $propertyName
     * @return void
     */
    public function Flow_Persistence_memorizeCleanState($propertyName = null)
    {
    }


    /**
     * A stub to satisfy the Flow Proxy Interface
     */
    public function __wakeup()
    {
    }
}
