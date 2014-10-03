<?php
namespace TYPO3\Flow\Security\Aspect;

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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Exception\NoTokensAuthenticatedException;

/**
 * An aspect which centralizes the logging of security relevant actions.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class LoggingAspect {

	/**
	 * @var \TYPO3\Flow\Log\SecurityLoggerInterface
	 * @Flow\Inject
	 */
	protected $securityLogger;

	/**
	 * @var boolean
	 */
	protected $alreadyLoggedAuthenticateCall = FALSE;

	/**
	 * Logs calls and results of the authenticate() method of the Authentication Manager
	 *
	 * @Flow\After("within(TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface) && method(.*->authenticate())")
	 * @param JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @throws \Exception
	 */
	public function logManagerAuthenticate(JoinPointInterface $joinPoint) {
		if ($joinPoint->hasException()) {
			$exception = $joinPoint->getException();
			if (!$exception instanceof NoTokensAuthenticatedException) {
				$this->securityLogger->log(sprintf('Authentication failed: "%s" #%d', $exception->getMessage(), $exception->getCode()), LOG_NOTICE);
			}
			throw $exception;
		} elseif ($this->alreadyLoggedAuthenticateCall === FALSE) {
			/** @var AuthenticationManagerInterface $authenticationManager */
			$authenticationManager = $joinPoint->getProxy();
			if ($authenticationManager->getSecurityContext()->getAccount() !== NULL) {
				$this->securityLogger->log(sprintf('Successfully re-authenticated tokens for account "%s"', $authenticationManager->getSecurityContext()->getAccount()->getAccountIdentifier()), LOG_INFO);
			} else {
				$this->securityLogger->log('No account authenticated', LOG_INFO);
			}
			$this->alreadyLoggedAuthenticateCall = TRUE;
		}
	}

	/**
	 * Logs calls and results of the logout() method of the Authentication Manager
	 *
	 * @Flow\AfterReturning("within(TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface) && method(.*->logout())")
	 * @param JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logManagerLogout(JoinPointInterface $joinPoint) {
		/** @var AuthenticationManagerInterface $authenticationManager */
		$authenticationManager = $joinPoint->getProxy();
		$securityContext = $authenticationManager->getSecurityContext();
		if (!$securityContext->isInitialized()) {
			return;
		}
		$accountIdentifiers = array();
		foreach ($securityContext->getAuthenticationTokens() as $token) {
			/** @var $account Account */
			$account = $token->getAccount();
			if ($account !== NULL) {
				$accountIdentifiers[] = $account->getAccountIdentifier();
			}
		}
		$this->securityLogger->log(sprintf('Logged out %d account(s). (%s)', count($accountIdentifiers), implode(', ', $accountIdentifiers)), LOG_INFO);
	}

	/**
	 * Logs calls and results of the authenticate() method of an authentication provider
	 *
	 * @Flow\AfterReturning("within(TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface) && method(.*->authenticate())")
	 * @param JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logPersistedUsernamePasswordProviderAuthenticate(JoinPointInterface $joinPoint) {
		$token = $joinPoint->getMethodArgument('authenticationToken');

		switch ($token->getAuthenticationStatus()) {
			case TokenInterface::AUTHENTICATION_SUCCESSFUL :
				$this->securityLogger->log(sprintf('Successfully authenticated token: %s', $token), LOG_NOTICE, array(), 'TYPO3.Flow', $joinPoint->getClassName(), $joinPoint->getMethodName());
				$this->alreadyLoggedAuthenticateCall = TRUE;
			break;
			case TokenInterface::WRONG_CREDENTIALS :
				$this->securityLogger->log(sprintf('Wrong credentials given for token: %s', $token) , LOG_WARNING, array(), 'TYPO3.Flow', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
			case TokenInterface::NO_CREDENTIALS_GIVEN :
				$this->securityLogger->log(sprintf('No credentials given or no account found for token: %s', $token), LOG_WARNING, array(), 'TYPO3.Flow', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
		}
	}

	/**
	 * Logs calls and result of vote() for method privileges
	 *
	 * @Flow\After("method(TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilege->vote())")
	 * @param JoinPointInterface $joinPoint
	 * @return void
	 */
	public function logJoinPointAccessDecisions(JoinPointInterface $joinPoint) {
		$subjectJoinPoint = $joinPoint->getMethodArgument('subject');
		$decision = $joinPoint->getResult() === TRUE ? 'GRANTED' : 'DENIED';
		$message = sprintf('Decided "%s" on method call %s::%s().', $decision, $subjectJoinPoint->getClassName(), $subjectJoinPoint->getMethodName());
		$this->securityLogger->log($message, \LOG_INFO);
	}

	/**
	 * Logs calls and result of isPrivilegeTargetGranted()
	 *
	 * @Flow\After("method(TYPO3\Flow\Security\Authorization\PrivilegeManager->isPrivilegeTargetGranted())")
	 * @param JoinPointInterface $joinPoint
	 * @return void
	 */
	public function logPrivilegeAccessDecisions(JoinPointInterface $joinPoint) {
		$decision = $joinPoint->getResult() === TRUE ? 'GRANTED' : 'DENIED';
		$message = sprintf('Decided "%s" on privilege "%s".', $decision, $joinPoint->getMethodArgument('privilegeTargetIdentifier'));
		$this->securityLogger->log($message, \LOG_INFO);
	}
}
