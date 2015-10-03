<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Method;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * Contract for a privilege used to restrict method calls
 */
interface MethodPrivilegeInterface extends PrivilegeInterface
{
    /**
     * Returns TRUE, if this privilege covers the given method
     *
     * @param string $className
     * @param string $methodName
     * @return bool
     */
    public function matchesMethod($className, $methodName);

    /**
     * Returns the pointcut filter composite, matching all methods covered by this privilege
     *
     * @return PointcutFilterComposite
     */
    public function getPointcutFilterComposite();
}
