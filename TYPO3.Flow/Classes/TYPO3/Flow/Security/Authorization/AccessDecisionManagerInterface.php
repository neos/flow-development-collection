<?php
namespace TYPO3\Flow\Security\Authorization;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for an access decision manager.
 *
 */
interface AccessDecisionManagerInterface
{
    /**
     * Decides if access should be granted on the given object in the current security context
     *
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
     * @return void
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function decideOnJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint);

    /**
     * Decides if access should be granted on the given resource in the current security context
     *
     * @param string $resource The resource to decide on
     * @return void
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function decideOnResource($resource);

    /**
     * Returns TRUE if access is granted on the given resource in the current security context
     *
     * @param string $resource The resource to decide on
     * @return boolean TRUE if access is granted, FALSE otherwise
     */
    public function hasAccessToResource($resource);
}
