<?php
namespace TYPO3\Flow\Security\Authorization\Interceptor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;

/**
 * This is the main security interceptor, which enforces the current security policy and is usually called by the central security aspect:
 *
 * 1. If authentication has not been performed (flag is set in the security context) the configured authentication manager is called to authenticate its tokens
 * 2. If a AuthenticationRequired exception has been thrown we look for an authentication entry point in the active tokens to redirect to authentication
 * 3. Then the configured AccessDecisionManager is called to authorize the request/action
 *
 * @Flow\Scope("singleton")
 */
class PolicyEnforcement implements \TYPO3\Flow\Security\Authorization\InterceptorInterface {

	/**
	 * The authentication manager
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * The access decision manager
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * The current joinpoint
	 * @var \TYPO3\Flow\Aop\JoinPointInterface
	 */
	protected $joinPoint;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager The authentication manager
	 * @param \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface $accessDecisionManager The access decision manager
	 */
	public function __construct(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager, \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface $accessDecisionManager) {
		$this->authenticationManager = $authenticationManager;
		$this->accessDecisionManager = $accessDecisionManager;
	}

	/**
	 * Sets the current joinpoint for this interception
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 */
	public function setJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$this->joinPoint = $joinPoint;
	}

	/**
	 * Invokes the security interception
	 *
	 * @return boolean TRUE if the security checks was passed
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException
	 * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException if an entity could not be found (assuming it is bound to the current session), causing a redirect to the authentication entrypoint
	 */
	public function invoke() {
		try {
			$this->authenticationManager->authenticate();
		} catch (\Doctrine\ORM\EntityNotFoundException $exception) {
			throw new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException('Could not authenticate. Looks like a broken session.', 1358971444, $exception);
		}
		$this->accessDecisionManager->decideOnJoinPoint($this->joinPoint);
	}
}

?>