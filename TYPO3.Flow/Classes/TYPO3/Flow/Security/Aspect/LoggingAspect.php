<?php
namespace TYPO3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect which centralizes the logging of security relevant actions.
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Aspect
 */
class LoggingAspect {

	/**
	 * @var \TYPO3\FLOW3\Log\SecurityLoggerInterface
	 * @FLOW3\Inject
	 */
	protected $securityLogger;

	/**
	 * @var boolean
	 */
	protected $alreadyLoggedAuthenticateCall = FALSE;

	/**
	 * Logs calls and results of the authenticate() method of the Authentication Manager
	 *
	 * @FLOW3\After("within(TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface) && method(.*->authenticate())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @throws \Exception
	 */
	public function logManagerAuthenticate(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		if ($joinPoint->hasException()) {
			$exception = $joinPoint->getException();
			$this->securityLogger->log('Authentication failed: "' . $exception->getMessage() . '" #' . $exception->getCode(), LOG_NOTICE);
			throw $exception;
		} elseif ($this->alreadyLoggedAuthenticateCall === FALSE) {
			if ($joinPoint->getProxy()->getSecurityContext()->getAccount() !== NULL) {
				$this->securityLogger->log('Successfully re-authenticated tokens for account "' . $joinPoint->getProxy()->getSecurityContext()->getAccount()->getAccountIdentifier() . '"', LOG_INFO);
			} else {
				$this->securityLogger->log('No account authenticated', LOG_INFO);
			}
			$this->alreadyLoggedAuthenticateCall = TRUE;
		}
	}

	/**
	 * Logs calls and results of the logout() method of the Authentication Manager
	 *
	 * @FLOW3\AfterReturning("within(TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface) && method(.*->logout())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logManagerLogout(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$securityContext = $joinPoint->getProxy()->getSecurityContext();
		if (!$securityContext->isInitialized()) {
			return;
		}
		$accountIdentifiers = array();
		foreach ($securityContext->getAuthenticationTokens() as $token) {
			$account = $token->getAccount();
			if ($account !== NULL) {
				$accountIdentifiers[] = $account->getAccountIdentifier();
			}
		}
		$this->securityLogger->log('Logged out ' . count($accountIdentifiers) . ' account(s). (' . implode(', ', $accountIdentifiers) . ')', LOG_INFO);
	}

	/**
	 * Logs calls and results of the authenticate() method of an authentication provider
	 *
	 * @FLOW3\AfterReturning("within(TYPO3\FLOW3\Security\Authentication\AuthenticationProviderInterface) && method(.*->authenticate())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logPersistedUsernamePasswordProviderAuthenticate(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$token = $joinPoint->getMethodArgument('authenticationToken');

		switch ($token->getAuthenticationStatus()) {
			case \TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL :
				$this->securityLogger->log('Successfully authenticated token: ' . $token, LOG_NOTICE, array(), 'TYPO3.FLOW3', $joinPoint->getClassName(), $joinPoint->getMethodName());
				$this->alreadyLoggedAuthenticateCall = TRUE;
			break;
			case \TYPO3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS :
				$this->securityLogger->log('Wrong credentials given for token: ' . $token, LOG_WARNING, array(), 'TYPO3.FLOW3', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
			case \TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN :
				$this->securityLogger->log('No credentials given or no account found for token: ' . $token, LOG_WARNING, array(), 'TYPO3.FLOW3', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
		}
	}

	/**
	 * Logs calls and results of decideOnJoinPoint()
	 *
	 * @FLOW3\AfterThrowing("method(TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager->decideOnJoinPoint())")
	 *
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint
	 * @throws \Exception
	 * @return void
	 */
	public function logJoinPointAccessDecisions(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$exception = $joinPoint->getException();

		$subjectJoinPoint = $joinPoint->getMethodArgument('joinPoint');
		$message = $exception->getMessage() . ' to method ' . $subjectJoinPoint->getClassName() . '::' . $subjectJoinPoint->getMethodName() . '().';

		$this->securityLogger->log($message, \LOG_INFO);

		throw $exception;
	}
}

?>