<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Method;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;

/**
 * A method privilege subject
 */
class MethodPrivilegeSubject implements PrivilegeSubjectInterface
{
    /**
     * @var JoinPointInterface
     */
    protected $joinPoint;

    /**
     * @param JoinPointInterface $joinPoint
     * @return void
     */
    public function __construct(JoinPointInterface $joinPoint)
    {
        $this->joinPoint = $joinPoint;
    }

    /**
     * @return JoinPointInterface
     */
    public function getJoinPoint()
    {
        return $this->joinPoint;
    }
}
